<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash;

use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\SearchResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\SearchResults;

class Search
{
    public function __construct(
        private readonly Client $client,
        public readonly string $query,
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly string $orderBy = 'relevant',
        public readonly ?string $orientation = null,
    ) {}

    public function get(): SearchResults
    {
        $params = [
            'query' => $this->query,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'order_by' => $this->orderBy,
        ];

        if ($this->orientation) {
            $params['orientation'] = $this->orientation;
        }

        $response = $this->client->get('https://api.unsplash.com/search/photos', $params);

        $photos = collect($response->json('results', []))
            ->map(fn (array $photo) => SearchResult::fromUnsplashData($photo));

        return new SearchResults(
            total: $response->json('total'),
            totalPages: $response->json('total_pages'),
            photos: $photos,
        );
    }
}
