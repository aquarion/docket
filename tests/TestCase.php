<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis connection for tests to avoid connecting to actual Redis instance
        $this->mock('redis', function ($mock) {
            return $mock;
        });
    }
}
