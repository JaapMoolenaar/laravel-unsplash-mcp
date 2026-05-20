<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;

class UnsplashMcpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/unsplash-mcp.php', 'unsplash-mcp');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/unsplash-mcp.php' => config_path('unsplash-mcp.php'),
        ], 'unsplash-mcp-config');

        Mcp::local('unsplash', UnsplashServer::class);
    }
}
