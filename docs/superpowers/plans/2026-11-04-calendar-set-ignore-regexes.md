# Per-Calendar-Set Ignore Patterns Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let each calendar set carry its own list of "ignore events matching this regex" patterns, editable on `/manage`.

**Architecture:** A nullable JSON column `filter_regexes` on `calendar_sets` holds the patterns. The existing set API reads and writes it, `docket.js` emits the effective list as `FILTER_OUT_REGEXES`, and the existing `DocketCalendar.filterReason()` does the matching. The `/manage` set modal gets a textarea with inline validation and a "try a title" tester.

**Tech Stack:** Laravel 12 / PHP 8.3 (PHPUnit feature tests, SQLite in-memory), vanilla JS (`calendar-manager.js`, `docket-calendar.js`), Biome for JS lint.

**Spec:** `docs/superpowers/specs/2026-11-04-calendar-set-ignore-regexes-design.md`

## Global Constraints

- `filter_regexes` is a nullable JSON column on `calendar_sets`. `null` = use `config('calendars.default_filter_regexes')`, shipped as `['^/']`. `[]` = hide nothing. A non-empty list **replaces** the default (never merged).
- API validation: `nullable|array|max:20`; each item a string, max 200 chars, non-empty after trim; duplicates removed; must compile as a PCRE pattern. The PCRE check is a sanity check only.
- Matching is case-sensitive.
- The hardcoded "summary starts with `/`" rule in `DocketCalendar.filterReason()` stays as is.
- Sets not in the database always use the default list.
- Invalid patterns at runtime are skipped with one logged warning per pattern, never thrown.
- `docket.js` keeps `Cache-Control: no-cache`; no new caching.
- Out of scope: case-insensitive option, `/pattern/flags` syntax, per-source rules, editing `filter_out` from the UI, server-side filtering.
- Setup: run `composer install` and `npm ci` first (vendor and node_modules are not present in a fresh checkout). Tests: `php artisan test`. JS lint: `npx @biomejs/biome check resources/js`.

## Review Focus

- A set that has never been configured (`filter_regexes` null) still hides `/`-prefixed events: `docket.js` must emit `["^/"]` for it (Task 3).
- A pattern containing a slash, such as `^\/`, must pass server validation (Task 2).
- Blank lines, whitespace-only lines and duplicate lines in the textarea must not become patterns (Tasks 2 and 5).
- A pattern that is valid PCRE but invalid JavaScript (for example `(?P<n>x)`) must be skipped with a warning while the set's other patterns still apply (Task 4).
- Clearing the textarea saves `[]` (hide nothing) while "Reset to default" saves `null`; the two must not be confused (Task 5).

---

### Task 1: Storage, config default, and effective-list resolver

**Files:**
- Create: `database/migrations/2026_11_04_000000_add_filter_regexes_to_calendar_sets_table.php`
- Modify: `app/Models/CalendarSet.php` (add to `$fillable`, add to `$casts`)
- Modify: `config/calendars.php` (add `default_filter_regexes` next to `filter_out`)
- Modify: `app/Services/CalendarService.php` (add method)
- Test: `tests/Feature/CalendarSetFilterRegexesTest.php` (create)

**Interfaces:**
- Produces: `CalendarSet::$filter_regexes` (`?array`); `config('calendars.default_filter_regexes')` (`array<string>`, default `['^/']`); `CalendarService::filterRegexesForSet(string $setKey): array` returning the set's list, or the default when the set is missing from the database or its value is `null`.

- [ ] **Step 1: Write the failing tests** in `CalendarSetFilterRegexesTest` (class uses `RefreshDatabase`):
  - `test_unconfigured_set_uses_default_regexes`: create a `CalendarSet` with `key => 'work'` and no `filter_regexes`; assert `app(CalendarService::class)->filterRegexesForSet('work') === ['^/']`.
  - `test_empty_list_means_hide_nothing`: `filter_regexes => []`; assert result `=== []`.
  - `test_custom_list_replaces_default`: `filter_regexes => ['^Lunch$']`; assert `=== ['^Lunch$']`.
  - `test_unknown_set_uses_default`: `filterRegexesForSet('nope') === ['^/']`.
- [ ] **Step 2: Run** `php artisan test --filter=CalendarSetFilterRegexesTest`. Expected: FAIL (column / method missing).
- [ ] **Step 3: Add the migration** (`$table->json('filter_regexes')->nullable()`, with a `down()` that drops it), the `$fillable` entry, the `'filter_regexes' => 'array'` cast, and `'default_filter_regexes' => ['^/']` in `config/calendars.php` with a short docblock in the file's existing comment style.
- [ ] **Step 4: Implement** `public function filterRegexesForSet(string $setKey): array` in `CalendarService`: look up `CalendarSet::where('key', $setKey)->first()`; return `$set->filter_regexes` when the set exists and the value is not `null`, otherwise `config('calendars.default_filter_regexes')`.
- [ ] **Step 5: Run** the same command. Expected: PASS.
- [ ] **Step 6: Commit** `git commit -m "Add filter_regexes column and effective-list resolver"`.

### Task 2: API validation and persistence

**Files:**
- Modify: `app/Http/Controllers/Api/CalendarSetController.php` (`index`, `store`, `show`, `update`)
- Test: `tests/Feature/CalendarSetFilterRegexesTest.php`

**Interfaces:**
- Consumes: `CalendarSet::$filter_regexes` from Task 1.
- Produces: `POST /api/calendar-sets` and `PUT /api/calendar-sets/{id}` accept `filter_regexes`; `GET /api/calendar-sets` and `GET /api/calendar-sets/{id}` include `filter_regexes` (stored value, `null` included) on every set.

- [ ] **Step 1: Write the failing tests** (all `actingAs(User::factory()->create())`, using `postJson` / `putJson` / `getJson`):
  - `test_store_persists_filter_regexes`: post a valid set with `filter_regexes => ['^Lunch$', '^\/']` (the second contains a slash); assert 201 and the saved model's `filter_regexes` equals the posted list.
  - `test_update_absent_key_leaves_regexes_unchanged`: set has `['a']`; PUT only `name`; assert still `['a']`.
  - `test_update_null_resets_to_default_and_empty_list_hides_nothing`: PUT `filter_regexes => null` then assert stored `null`; PUT `[]` then assert stored `[]`.
  - `test_show_and_index_return_filter_regexes`: assert `data.filter_regexes` on show, and on each index row.
  - `test_blank_and_duplicate_patterns_are_normalised`: PUT `['  a ', '', '   ', 'a', 'b']`; assert stored `['a', 'b']`.
  - `test_invalid_filter_regexes_are_rejected` (data provider, each asserts 422 with error on `filter_regexes` or `filter_regexes.0`): 21 patterns; a 201-character pattern; a non-string item (`[123]`); an uncompilable pattern `'(unclosed'`; a non-array value `'abc'`.
- [ ] **Step 2: Run** the filter. Expected: FAIL.
- [ ] **Step 3: Implement.** Add a private `filterRegexRules(): array` to the controller returning `['filter_regexes' => 'nullable|array|max:20', 'filter_regexes.*' => ['string', 'max:200', <closure rule>]]`, merged into both `validate()` calls. The closure rule fails when `@preg_match("\x01".$value."\x01u", '') === false` (a control-character delimiter, so slashes in patterns need no escaping; message "Not a valid regular expression."). Add private `normaliseFilterRegexes(?array $patterns): ?array` that trims each item, drops empties, removes duplicates (preserving order) and returns `null` for `null` input. A whitespace-only item passes `string|max:200` validation and is dropped by this normalisation step. In `store`/`update`, apply it only when `$request->has('filter_regexes')`; add `filter_regexes` to the `create`/`update` payloads accordingly (in `update`, merge into the `$request->only([...])` array result). Add `'filter_regexes' => $set->filter_regexes` to the `show` payload and to each row of `index`.
- [ ] **Step 4: Run** the filter. Expected: PASS. Also run `php artisan test` to confirm nothing else broke.
- [ ] **Step 5: Commit** `git commit -m "Accept and return filter_regexes on calendar sets"`.

### Task 3: Deliver the effective list in docket.js

**Files:**
- Modify: `app/Http/Controllers/CalendarController.php` (`js()`, around line 146-162)
- Modify: `resources/views/docket-js.blade.php:21`
- Test: `tests/Feature/CalendarSetFilterRegexesTest.php`

**Interfaces:**
- Consumes: `CalendarService::filterRegexesForSet()` from Task 1.
- Produces: the `docket-js` view receives `$filter_regexes` (`array<string>`); the served script contains `FILTER_OUT_REGEXES: <json array>` for the requested set.

- [ ] **Step 1: Write the failing tests**, requesting `/docket.js?calendar_set=work` for a global set (`user_id` null, so a guest can request it):
  - `test_docket_js_emits_default_for_unconfigured_set`: assert the body contains `FILTER_OUT_REGEXES: ["^\/"]`.
  - `test_docket_js_emits_custom_list`: set `['^Lunch$', 'Standup']`; assert body contains `FILTER_OUT_REGEXES: ["^Lunch$","Standup"]`.
  - `test_docket_js_emits_empty_list`: set `[]`; assert body contains `FILTER_OUT_REGEXES: []`.
  - `test_other_sets_rules_do_not_leak`: two global sets, one with custom rules; requesting the other still emits the default.
- [ ] **Step 2: Run** the filter. Expected: FAIL.
- [ ] **Step 3: Implement.** In `CalendarController@js`, pass `'filter_regexes' => $this->calendarService->filterRegexesForSet($filteredConfig['calendar_set_id'])` to the view. In the blade, replace the hardcoded array with `{!! json_encode($filter_regexes ?? config('calendars.default_filter_regexes')) !!}`.
- [ ] **Step 4: Run** the filter, then the full suite. Expected: PASS.
- [ ] **Step 5: Commit** `git commit -m "Emit per-set filter regexes in docket.js"`.

### Task 4: Skip invalid patterns at runtime

**Files:**
- Modify: `resources/js/docket-calendar.js` (`DocketCalendar.filterReason`, which builds `new RegExp(regexString)`)

**Interfaces:**
- Consumes: `FILTER_OUT_REGEXES` from Task 3.
- Produces: `filterReason(summary)` keeps its existing signature and return values (`string` reason or `null`); an invalid pattern is skipped, never thrown.

There is no JavaScript test harness in the repo, so this task is verified with a scratch script, not a committed test.

- [ ] **Step 1: Write a scratch repro** in the scratchpad directory (not the repo): load `docket-calendar.js` via `new Function` with stub globals (`DocketConfig = { constants: { FILTER_OUT_REGEXES: ['(?P<n>x)', '^Lunch$'] } }`, a `NotificationUtils` stub recording `warning` calls), call `DocketCalendar.filterReason('Lunch')` and `filterReason('Other')`, and print results plus the recorded warnings. `ical.js` is not needed for this function; stub `ICAL` as `{}`.
- [ ] **Step 2: Run** it with node. Expected before the change: throws `SyntaxError: Invalid regular expression` on the first pattern.
- [ ] **Step 3: Implement.** Wrap `new RegExp(regexString)` in `try/catch`; on failure call `NotificationUtils.warning` once per distinct pattern (track already-warned patterns in a module-level object on `DocketCalendar`) and continue to the next pattern.
- [ ] **Step 4: Run** the scratch script. Expected: `filterReason('Lunch')` returns `Filtered by regex ^Lunch$`; `filterReason('Other')` returns `null`; exactly one warning for `(?P<n>x)` even after calling twice.
- [ ] **Step 5: Lint** `npx @biomejs/biome check resources/js/docket-calendar.js`. Expected: no errors.
- [ ] **Step 6: Commit** `git commit -m "Skip invalid ignore patterns instead of throwing"`.

### Task 5: `/manage` UI

**Files:**
- Modify: `resources/views/manage.blade.php` (set modal form, after the "Set as default" group around line 164-168)
- Modify: `resources/js/calendar-management/calendar-manager.js` (`showAddSetModal`, `editSet`, `handleSetSubmit`, plus new helpers)

**Interfaces:**
- Consumes: `filter_regexes` on `GET /api/calendar-sets/{id}` and the write contract from Task 2.
- Produces on `CalendarManager`:
  - `parseFilterRegexes(text: string): string[]` – splits on newlines, trims, drops blanks, removes duplicates keeping order.
  - `validateFilterRegexes(patterns: string[]): {pattern: string, message: string}[]` – one entry per pattern for which `new RegExp(pattern)` throws.
  - `firstMatchingPattern(title: string, patterns: string[]): string | null` – the first valid pattern that matches `title`, ignoring invalid ones.
  - `currentFilterRegexes: string[] | null` state: `null` when the set is using the default.

Markup requirements (ids are fixed so the JS can find them): a form group containing `<label for="set-filter-regexes">Ignore events matching:</label>`, `<textarea id="set-filter-regexes" rows="4" spellcheck="false">`, helper text "Regular expressions, case-sensitive, one per line. Using the default (`^/`) unless you change this.", `<ul id="set-filter-regexes-errors">`, a "Reset to default" button `id="set-filter-regexes-reset"`, and a "Try a title" `<input id="set-filter-regexes-try">` with a result `<span id="set-filter-regexes-try-result">`.

- [ ] **Step 1: Write a scratch check** in the scratchpad (not the repo) that loads `calendar-manager.js` with stub globals and asserts the three pure helpers: `parseFilterRegexes("  a \n\n b\na")` → `['a','b']`; `validateFilterRegexes(['(unclosed','ok'])` returns one entry for `(unclosed`; `firstMatchingPattern('Lunch', ['(unclosed','^Lunch$'])` → `'^Lunch$'`, and `firstMatchingPattern('Other', ['^Lunch$'])` → `null`. Run it; expected FAIL (helpers missing).
- [ ] **Step 2: Implement the three helpers** on `CalendarManager`, then re-run the scratch check. Expected: PASS.
- [ ] **Step 3: Add the markup** described above.
- [ ] **Step 4: Wire the modal.** `showAddSetModal`: textarea empty, `currentFilterRegexes = null`, helper text shows "using default" state. `editSet`: if `set.filter_regexes` is `null` show the default state with the textarea showing `^/` as a placeholder-style hint but empty; otherwise fill it with the patterns joined by newlines and set `currentFilterRegexes` to the array. Typing in the textarea sets `currentFilterRegexes = parseFilterRegexes(value)`, re-renders the error list from `validateFilterRegexes`, and re-runs the tester. "Reset to default" clears the textarea and sets `currentFilterRegexes = null`.
- [ ] **Step 5: Submit.** In `handleSetSubmit` add `filter_regexes: this.currentFilterRegexes` to `data`, but stop with an error notification (no request) if `validateFilterRegexes` returns anything. Result: untouched/reset sends `null`; a cleared-by-typing textarea (user deleted every line after having patterns) sends `[]`.
- [ ] **Step 6: Lint** `npx @biomejs/biome check resources/js`. Expected: no errors.
- [ ] **Step 7: Manual browser check** (state in the PR that this was manual): start the app, open `/manage`, edit a set. Verify: default state shows the helper text; typing `^Lunch$` then "Try a title" `Lunch` shows it would be hidden by `^Lunch$`; typing `(unclosed` shows an inline error and Save refuses; "Reset to default" then Save leaves the set at `null` (confirm via `GET /api/calendar-sets/{id}`); deleting all lines of a custom list then Save stores `[]`.
- [ ] **Step 8: Commit** `git commit -m "Add ignore-pattern editor to the calendar set modal"`.

### Task 6: Docs, final checks, and PR update

**Files:**
- Modify: `README.md` (add a short "Ignoring events" subsection where calendar sets are described; find it with `grep -n "calendar set" README.md`)

- [ ] **Step 1: Document** the feature in two or three sentences plus one example pattern (`^Lunch$`), including that a custom list replaces the default `^/` and that titles starting with `/` stay hidden regardless.
- [ ] **Step 2: Run** `php artisan test` and `npx @biomejs/biome check resources/js`. Expected: all pass, no lint errors. Report the actual output.
- [ ] **Step 3: Commit** `git commit -m "Document per-set ignore patterns"`, then `git push`.
- [ ] **Step 4: Update PR #314** (title "Per-calendar-set ignore patterns", description summarising the spec and the implementation, noting the JS and UI parts were verified manually because the repo has no JS test harness). Mark it ready for review only if the user asks.
