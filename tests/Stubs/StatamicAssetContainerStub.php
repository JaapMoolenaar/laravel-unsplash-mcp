<?php

namespace JaapMoolenaar\UnsplashMcp\Tests\Stubs;

use Illuminate\Support\Facades\Facade;

class StatamicAssetContainerStub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'statamic.asset-container';
    }
}
