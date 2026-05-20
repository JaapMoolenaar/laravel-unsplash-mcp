<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash\DTO;

readonly class DownloadResult
{
    public function __construct(
        public string $tempPath,
        public string $fileName,
        public PhotoResult $photo,
    ) {}
}
