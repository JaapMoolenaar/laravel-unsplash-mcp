<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Registrar;

use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;

interface AssetRegistrar
{
    public function register(DownloadResult $result): RegisteredResult;
}
