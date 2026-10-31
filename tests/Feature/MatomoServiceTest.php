<?php

namespace Tests\Feature;

use App\Services\MatomoService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Throwable;

class MatomoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.matomo.url' => 'https://stat.istic.net',
            'services.matomo.auth_token' => 'test-token',
            'services.matomo.goals' => ['registration' => 'Registration complete'],
            'app.url' => 'https://app.example.com',
            'app.name' => 'Starter',
        ]);
    }

    public function test_returns_null_when_matomo_url_is_not_configured(): void
    {
        config(['services.matomo.url' => null]);

        $this->assertNull((new MatomoService)->getConfig());
    }

    public function test_returns_null_when_matomo_auth_token_is_not_configured(): void
    {
        config(['services.matomo.auth_token' => null]);

        $this->assertNull((new MatomoService)->getConfig());
    }

    public function test_returns_null_and_logs_a_warning_with_diagnostic_context_when_the_matomo_api_is_unreachable(): void
    {
        Http::fake([
            'stat.istic.net/*' => Http::response(null, 500),
        ]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Matomo config lookup failed', Mockery::on(function (array $context) {
                return $context['app_url'] === 'https://app.example.com'
                    && $context['matomo_url'] === 'https://stat.istic.net'
                    && $context['exception'] instanceof Throwable
                    && is_string($context['message']);
            }));

        $this->assertNull((new MatomoService)->getConfig());
    }

    public function test_matches_an_existing_matomo_site_ignoring_a_trailing_slash_difference(): void
    {
        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([[
                        'idsite' => '3',
                        'main_url' => 'https://app.example.com/',
                        'name' => 'Starter',
                    ]]);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([
                        ['idgoal' => '1', 'name' => 'Registration complete'],
                    ]);
                }

                return Http::response([], 200);
            },
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame(3, $config['site_id']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'SitesManager.addSite'));
    }

    public function test_uses_an_existing_matomo_site_when_app_url_matches(): void
    {
        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([[
                        'idsite' => '3',
                        'main_url' => 'https://app.example.com',
                        'name' => 'Starter',
                    ]]);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([
                        ['idgoal' => '1', 'name' => 'Registration complete'],
                    ]);
                }

                return Http::response([], 200);
            },
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame(3, $config['site_id']);
        Http::assertSentCount(2);
    }

    public function test_creates_a_new_matomo_site_when_none_matches_app_url(): void
    {
        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([]);
                }

                if ($method === 'SitesManager.addSite') {
                    return Http::response(['value' => '7']);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([]);
                }

                if ($method === 'Goals.addGoal') {
                    return Http::response(['value' => '1']);
                }

                return Http::response([], 200);
            },
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame(7, $config['site_id']);
    }

    public function test_creates_missing_goals(): void
    {
        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([['idsite' => '3', 'main_url' => 'https://app.example.com']]);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([]);
                }

                if ($method === 'Goals.addGoal') {
                    return Http::response(['value' => '5']);
                }

                return Http::response([], 200);
            },
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame(5, $config['goals']['registration']);
    }

    public function test_uses_existing_goals_instead_of_creating_new_ones(): void
    {
        Http::fake([
            'stat.istic.net/*' => function ($request) {
                $method = $request->data()['method'] ?? '';

                if ($method === 'SitesManager.getAllSites') {
                    return Http::response([['idsite' => '3', 'main_url' => 'https://app.example.com']]);
                }

                if ($method === 'Goals.getGoals') {
                    return Http::response([
                        ['idgoal' => '2', 'name' => 'Registration complete'],
                    ]);
                }

                return Http::response([], 200);
            },
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame(2, $config['goals']['registration']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'Goals.addGoal'));
    }

    public function test_caches_the_config_and_does_not_call_the_api_on_subsequent_requests(): void
    {
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

        $service = new MatomoService;
        $service->getConfig();
        $service->getConfig();

        Http::assertSentCount(2); // only the first call hits the API
    }

    public function test_returns_the_tracker_url_in_the_config(): void
    {
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

        $config = (new MatomoService)->getConfig();

        $this->assertSame('https://stat.istic.net', $config['tracker_url']);
    }

    public function test_skips_the_goals_api_when_no_goals_are_configured(): void
    {
        config(['services.matomo.goals' => []]);

        Http::fake([
            'stat.istic.net/*' => Http::response([['idsite' => '3', 'main_url' => 'https://app.example.com']]),
        ]);

        $config = (new MatomoService)->getConfig();

        $this->assertSame([], $config['goals']);
        Http::assertSentCount(1);
    }

    public function test_caches_a_failed_lookup_and_does_not_retry_the_api_until_it_expires(): void
    {
        Http::fake([
            'stat.istic.net/*' => Http::response(null, 500),
        ]);
        Log::shouldReceive('warning')->once();

        $service = new MatomoService;

        $this->assertNull($service->getConfig());
        $this->assertNull($service->getConfig());
        Http::assertSentCount(1);

        $this->travel(6)->minutes();

        Log::shouldReceive('warning')->once();
        $this->assertNull($service->getConfig());
        Http::assertSentCount(2);
    }

    public function test_returns_null_when_the_matomo_api_connection_times_out(): void
    {
        Http::fake([
            'stat.istic.net/*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);
        Log::shouldReceive('warning')->once();

        $this->assertNull((new MatomoService)->getConfig());
    }
}
