# Per-Calendar-Set Ignore Patterns Design

**Date:** 2026-11-04
**Status:** Draft, awaiting review

## Overview

Docket can already hide events whose title matches a regex, but the list is
hardcoded as `["^\/"]` in `resources/views/docket-js.blade.php` (events whose
title starts with `/`). This adds a way to manage those patterns from the UI,
separately for each calendar set, so a "work" set can hide different things
from a "home" set.

## Decisions

| Decision | Choice | Reason |
|---|---|---|
| Rule scope | Per calendar set | Chosen by the repo owner; matches how sets already scope sources |
| Storage | Nullable JSON column `filter_regexes` on `calendar_sets` | Patterns travel with the set through the existing API and `docket.js` view; no new model |
| Default | `null` means the built-in default `["^/"]` | Existing sets behave exactly as today with no data migration |
| "Hide nothing" | Explicit empty array `[]` | Distinguishes "no rules" from "never configured" |
| Case sensitivity | Case-sensitive | Matches current behaviour; a case-insensitive option is out of scope |
| Rejected: separate filters table | | More models, endpoints and UI for no current need |
| Rejected: browser storage | | Rules would differ per device and not follow a wall display |

---

## 1. Data

New migration adds `filter_regexes` (`json`, nullable) to `calendar_sets`.
`CalendarSet` adds it to `$fillable` and casts it to `array`.

Semantics:

- `null`: use the default list from `config('calendars.default_filter_regexes')`,
  which ships as `['^/']`.
- `[]`: hide nothing by regex.
- Non-empty list: exactly these patterns, replacing the default (the default
  is **not** merged in, so a user who wants `^/` keeps it by listing it).

Sets that are not in the database (the config-file fallback sets in
`CalendarService`) always use the default list.

## 2. API

`CalendarSetController@store` and `@update` accept an optional
`filter_regexes`:

- `nullable|array|max:20`
- each item: `string|max:200`, non-empty after trimming; duplicates removed
- each item must also compile as a PCRE pattern (`@preg_match('/' . str_replace('/', '\/', $p) . '/u', '') !== false`).
  This is a sanity check only. PCRE and JavaScript syntax differ slightly, so
  the browser remains the authority (section 4).

`index` and `show` return `filter_regexes` (the stored value, `null` included,
so the UI can tell "default" from "custom").

Both write paths already call `CalendarService::clearCache()`, which drops the
15-minute `calendars:filtered:{setId}` cache. That is enough here because
`filter_regexes` is not part of the cached calendar config; it is read fresh
when `docket.js` is rendered (section 3). No new cache handling is needed.

## 3. Delivery to the page

`CalendarController@js` resolves the current set (it already has
`calendar_set_id`) and passes the effective pattern list to the `docket-js`
view. The view replaces the hardcoded array with
`FILTER_OUT_REGEXES: {!! json_encode($filter_regexes) !!}`.

`docket.js` is served with `Cache-Control: no-cache`, so a saved change is
picked up on the next page refresh without waiting for any cache.

## 4. Matching (JavaScript)

`DocketCalendar.filterReason()` in `resources/js/docket-calendar.js` already
applies `FILTER_OUT_LIST`, `FILTER_OUT_REGEXES` and the `/` prefix rule to
both ordinary events and moved occurrences of recurring events. It is reused
unchanged except for one fix: `new RegExp(pattern)` is wrapped in `try/catch`.
An invalid pattern is skipped with a single logged warning per pattern, rather
than throwing and causing the whole event to be dropped by the caller's catch.

The separate hardcoded "summary starts with `/`" rule in `filterReason` stays
as is. It duplicates the default `^/` pattern, so when a set has a custom list
without `^/`, events starting with `/` would still be hidden. This is
intentional: it is the long-standing convention for private events, so it is
not made configurable here.

## 5. UI (`/manage`)

In the calendar-set editor (`resources/views/manage.blade.php`,
`resources/js/calendar-management/calendar-manager.js`):

- A textarea labelled "Ignore events matching", one pattern per line, with
  helper text ("Regular expressions, case-sensitive. Leave unchanged to use the
  default `^/`.") and a "Reset to default" action that sends `null`.
- As the user types, each line is validated with `new RegExp(line)`; invalid
  lines are flagged inline and block saving.
- A "Try a title" input shows live whether the entered title would be hidden
  and by which pattern.
- Saving sends `filter_regexes` as part of the existing set update request.

## 6. Testing

PHP feature tests (new `tests/Feature/CalendarSetFilterRegexesTest.php`):

- store/update persist `filter_regexes`; `null` and `[]` round-trip distinctly
- validation rejects too many patterns, over-long patterns, non-strings and
  patterns that do not compile
- `GET /docket.js?calendar_set=...` emits the default list for a `null` set,
  the custom list for a configured set, and `[]` when empty
- a set without custom rules is unaffected by another set's rules

There is no JavaScript test harness in the repo, so the matching change and
the `/manage` UI are verified by hand in a browser, and the pull request will
say so. Existing browser tests in `tests/Browser` are extended only if they
already cover the set editor.

## Out of scope

- Case-insensitive matching or `/pattern/flags` syntax
- Per-source rules, or rules shared across sets
- Editing the exact-title `filter_out` list from the UI
- Server-side filtering (matching stays in the browser, as it is today)

## Open questions

None blocking. If the PCRE sanity check proves too strict for patterns that
are valid in JavaScript, it can be dropped and left to the client check.
