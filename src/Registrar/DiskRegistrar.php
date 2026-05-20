<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Registrar;

use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;

class DiskRegistrar extends AbstractRegistrar
{
    public function register(DownloadResult $result): RegisteredResult
    {
        return $this->moveFromTemp($result, config('unsplash-mcp.disk.name', 'public'));
    }
}
