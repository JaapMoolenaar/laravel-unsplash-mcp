<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Registrar;

readonly class RegisteredResult
{
    public function __construct(
        public string $fileName,
        public string $url,
    ) {}
}
