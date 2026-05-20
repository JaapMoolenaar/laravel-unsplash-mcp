<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash\DTO;

use Illuminate\Support\Collection;

readonly class SearchResults
{
    public function __construct(
        public int $total,
        public int $totalPages,

        /** @var Collection<array-key, SearchResult> */
        public Collection $photos,
    ) {}
}
