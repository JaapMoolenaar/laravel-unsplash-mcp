<?php

namespace JaapMoolenaar\UnsplashMcp\Tests;

use JaapMoolenaar\UnsplashMcp\UnsplashMcpServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [UnsplashMcpServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('unsplash-mcp.access_key', 'test-key');
    }
}
