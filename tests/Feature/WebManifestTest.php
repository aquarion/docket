<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class WebManifestTest extends TestCase
{
    public function test_serves_a_web_manifest_pointing_at_the_generated_icons(): void
    {
        $this->get('/site.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', config('app.name'))
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.src', Vite::asset('resources/icons/web-app-manifest-192x192.png'))
            ->assertJsonPath('icons.1.src', Vite::asset('resources/icons/web-app-manifest-512x512.png'));
    }

    public function test_login_page_links_the_generated_icons_and_manifest(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(Vite::asset('resources/icons/favicon.svg'), false)
            ->assertSee(Vite::asset('resources/icons/apple-touch-icon.png'), false)
            ->assertSee(route('manifest'), false);
    }
}
