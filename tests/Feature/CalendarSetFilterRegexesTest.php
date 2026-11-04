<?php

namespace Tests\Feature;

use App\Models\CalendarSet;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarSetFilterRegexesTest extends TestCase
{
    use RefreshDatabase;

    private function makeSet(string $key, array $attributes = []): CalendarSet
    {
        return CalendarSet::create(array_merge([
            'key' => $key,
            'name' => ucfirst($key),
            'is_active' => true,
        ], $attributes));
    }

    public function test_unconfigured_set_uses_default_regexes(): void
    {
        $this->makeSet('work');

        $this->assertSame(['^/'], app(CalendarService::class)->filterRegexesForSet('work'));
    }

    public function test_empty_list_means_hide_nothing(): void
    {
        $this->makeSet('work', ['filter_regexes' => []]);

        $this->assertSame([], app(CalendarService::class)->filterRegexesForSet('work'));
    }

    public function test_custom_list_replaces_default(): void
    {
        $this->makeSet('work', ['filter_regexes' => ['^Lunch$']]);

        $this->assertSame(['^Lunch$'], app(CalendarService::class)->filterRegexesForSet('work'));
    }

    public function test_unknown_set_uses_default(): void
    {
        $this->assertSame(['^/'], app(CalendarService::class)->filterRegexesForSet('nope'));
    }

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_store_persists_filter_regexes(): void
    {
        $this->actingUser();

        $this->postJson('/api/calendar-sets', [
            'key' => 'work',
            'name' => 'Work',
            'filter_regexes' => ['^Lunch$', '^\/'],
        ])->assertCreated();

        $this->assertSame(['^Lunch$', '^\/'], CalendarSet::where('key', 'work')->first()->filter_regexes);
    }

    public function test_update_absent_key_leaves_regexes_unchanged(): void
    {
        $this->actingUser();
        $set = $this->makeSet('work', ['filter_regexes' => ['a']]);

        $this->putJson("/api/calendar-sets/{$set->id}", ['name' => 'Renamed'])->assertOk();

        $this->assertSame(['a'], $set->fresh()->filter_regexes);
    }

    public function test_update_null_resets_to_default_and_empty_list_hides_nothing(): void
    {
        $this->actingUser();
        $set = $this->makeSet('work', ['filter_regexes' => ['a']]);

        $this->putJson("/api/calendar-sets/{$set->id}", ['filter_regexes' => null])->assertOk();
        $this->assertNull($set->fresh()->filter_regexes);

        $this->putJson("/api/calendar-sets/{$set->id}", ['filter_regexes' => []])->assertOk();
        $this->assertSame([], $set->fresh()->filter_regexes);
    }

    public function test_show_and_index_return_filter_regexes(): void
    {
        $this->actingUser();
        $set = $this->makeSet('work', ['filter_regexes' => ['a']]);

        $this->getJson("/api/calendar-sets/{$set->id}")
            ->assertOk()
            ->assertJsonPath('data.filter_regexes', ['a']);

        $this->getJson('/api/calendar-sets')
            ->assertOk()
            ->assertJsonPath('data.0.filter_regexes', ['a']);
    }

    public function test_blank_and_duplicate_patterns_are_normalised(): void
    {
        $this->actingUser();
        $set = $this->makeSet('work');

        $this->putJson("/api/calendar-sets/{$set->id}", [
            'filter_regexes' => ['  a ', '', '   ', 'a', 'b'],
        ])->assertOk();

        $this->assertSame(['a', 'b'], $set->fresh()->filter_regexes);
    }

    /**
     * @dataProvider invalidFilterRegexes
     */
    public function test_invalid_filter_regexes_are_rejected(mixed $value): void
    {
        $this->actingUser();
        $set = $this->makeSet('work');

        $this->putJson("/api/calendar-sets/{$set->id}", ['filter_regexes' => $value])
            ->assertStatus(422);
    }

    public static function invalidFilterRegexes(): array
    {
        return [
            'too many patterns' => [array_map(fn ($i) => "p{$i}", range(1, 21))],
            'pattern too long' => [[str_repeat('a', 201)]],
            'non-string item' => [[123]],
            'uncompilable pattern' => [['(unclosed']],
            'not an array' => ['abc'],
        ];
    }
}
