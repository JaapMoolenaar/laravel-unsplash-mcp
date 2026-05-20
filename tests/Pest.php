<?php

use JaapMoolenaar\UnsplashMcp\Tests\Stubs\StatamicAssetContainerStub;
use JaapMoolenaar\UnsplashMcp\Tests\TestCase;
use Statamic\Facades\AssetContainer;

uses(TestCase::class)->in('.');

function fakeStatamic(): void
{
    if (! class_exists(AssetContainer::class)) {
        class_alias(
            StatamicAssetContainerStub::class,
            AssetContainer::class,
        );
    }

    // Bind a placeholder so the facade can resolve before Mockery swaps it.
    app()->bind('statamic.asset-container', fn () => new stdClass);
}
