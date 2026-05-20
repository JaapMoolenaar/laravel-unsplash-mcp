<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash\DTO;

readonly class SearchResult
{
    public function __construct(
        public string $id,
        public string $slug,
        public int $width,
        public int $height,
        public string $color,
        public ?string $description,
        public ?string $altDescription,

        public string $rawUrl,
        public string $fullUrl,
        public string $regularUrl,
        public string $smallUrl,
        public string $thumbUrl,

        public string $htmlLink,
        public string $downloadLink,
        public string $downloadLocationLink,

        public string $photographer,
        public string $photographerUrl,
    ) {}

    public static function fromUnsplashData(array $photo): static
    {
        return new static(
            id: $photo['id'],
            slug: $photo['slug'],
            width: $photo['width'],
            height: $photo['height'],
            color: $photo['color'],
            description: $photo['description'] ?? null,
            altDescription: $photo['alt_description'] ?? null,

            rawUrl: $photo['urls']['raw'],
            fullUrl: $photo['urls']['full'],
            regularUrl: $photo['urls']['regular'],
            smallUrl: $photo['urls']['small'],
            thumbUrl: $photo['urls']['thumb'],

            htmlLink: $photo['links']['html'],
            downloadLink: $photo['links']['download'],
            downloadLocationLink: $photo['links']['download_location'],

            photographer: $photo['user']['name'],
            photographerUrl: $photo['user']['links']['html'],
        );
    }
}
