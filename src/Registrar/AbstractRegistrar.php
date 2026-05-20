<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Registrar;

use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;

abstract class AbstractRegistrar implements AssetRegistrar
{
    protected function moveFromTemp(DownloadResult $result, string $diskName): RegisteredResult
    {
        $disk = Storage::disk($diskName);

        $disk->writeStream($result->fileName, Storage::disk(config('unsplash-mcp.temp_disk'))->readStream($result->tempPath));

        Storage::disk(config('unsplash-mcp.temp_disk'))->delete($result->tempPath);

        return new RegisteredResult(
            fileName: $result->fileName,
            url: $disk->url($result->fileName),
        );
    }
}
