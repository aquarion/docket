<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MatomoTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_does_not_render_the_matomo_tracker_when_matomo_is_not_configured(): void
    {
        config([
            'services.matomo.url' => null,
            'services.matomo.auth_token' => null,
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('matomo.js', false)
            ->assertDontSee('_paq', false);
    }

    public function test_renders_the_matomo_tracker_with_site_id_and_goals_when_configured(): void
    {
        config([
            'services.matomo.url' => 'https://stat.istic.net',
            'services.matomo.auth_token' => 'test-token',
            'services.matomo.goals' => ['registration' => 'Registration complete'],
            'app.url' => 'https://app.example.com',
        ]);

        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([['idsite' => '3', 'main_url' => 'https://app.example.com']]);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([['idgoal' => '1', 'name' => 'Registration complete']]);
                }

                return Http::response([], 200);
            },
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<script async src="https://stat.istic.net/matomo.js"></script>', false)
            ->assertSee("window._paq.push(['setSiteId', \"3\"]);", false)
            ->assertSee('window.matomoGoals = {"registration":1};', false);
    }
}
