<?php

use Illuminate\Support\Facades\Http;
use JaapMoolenaar\UnsplashMcp\Unsplash\Client;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\SearchResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RequestFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Search;

test('returns SearchResults with mapped photos', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    Http::fake([
        'api.unsplash.com/*' => Http::response([
            'total' => 1,
            'total_pages' => 1,
            'results' => [$photo],
        ]),
    ]);

    $result = (new Search(new Client, query: 'nature'))->get();

    expect($result->total)->toBe(1)
        ->and($result->totalPages)->toBe(1)
        ->and($result->photos)->toHaveCount(1)
        ->and($result->photos->first())->toBeInstanceOf(SearchResult::class)
        ->and($result->photos->first()->id)->toBe('abc123xyz');
});

test('passes query parameters to the API', function () {
    Http::fake(['*' => Http::response(['total' => 0, 'total_pages' => 0, 'results' => []])]);

    (new Search(new Client, query: 'forest', page: 2, perPage: 5, orderBy: 'latest', orientation: 'landscape'))->get();

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

        return $params['query'] === 'forest'
            && $params['page'] === '2'
            && $params['per_page'] === '5'
            && $params['order_by'] === 'latest'
            && $params['orientation'] === 'landscape';
    });
});

test('omits orientation param when null', function () {
    Http::fake(['*' => Http::response(['total' => 0, 'total_pages' => 0, 'results' => []])]);

    (new Search(new Client, query: 'forest', orientation: null))->get();

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

        return ! array_key_exists('orientation', $params);
    });
});

test('throws RequestFailed on non-2xx response', function () {
    Http::fake(['*' => Http::response([], 500)]);

    expect(fn () => (new Search(new Client, query: 'nature'))->get())
        ->toThrow(RequestFailed::class);
});
