<?php

namespace Tests\Feature;

use Tests\TestCase;

class VersionEndpointTest extends TestCase
{
    public function test_exposes_app_version_info_as_json_without_authentication(): void
    {
        config([
            'version.version' => '1.2.3',
            'version.pr_number' => '42',
            'version.branch' => 'main',
        ]);

        $this->getJson('/version')
            ->assertOk()
            ->assertExactJson([
                'version' => '1.2.3',
                'pr_number' => '42',
                'branch' => 'main',
            ]);
    }
}
