<?php

namespace Tests\Feature;

use App\Models\CalendarSet;
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
}
