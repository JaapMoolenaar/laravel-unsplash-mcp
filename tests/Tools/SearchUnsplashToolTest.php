<?php

use Illuminate\Support\Facades\Http;
use JaapMoolenaar\UnsplashMcp\Tools\SearchUnsplashTool;
use Laravel\Mcp\Request;

test('returns error when query is missing', function () {
    $response = (new SearchUnsplashTool)->handle(new Request([]));

    expect($response->isError())->toBeTrue();
});

test('returns error when query exceeds 50 characters', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => str_repeat('a', 51)]));

    expect($response->isError())->toBeTrue();
});

test('returns error for page less than 1', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'page' => -5]));

    expect($response->isError())->toBeTrue();
});

test('returns error for per_page greater than 30', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'per_page' => 100]));

    expect($response->isError())->toBeTrue();
});

test('returns error for per_page less than 1', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'per_page' => 0]));

    expect($response->isError())->toBeTrue();
});

test('returns error for invalid orientation', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'orientation' => 'diagonal']));

    expect($response->isError())->toBeTrue();
});

test('returns error for invalid order_by', function () {
    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'order_by' => 'random']));

    expect($response->isError())->toBeTrue();
});

test('passes order_by to the API', function () {
    Http::fake(['*' => Http::response(['total' => 0, 'total_pages' => 0, 'results' => []])]);

    (new SearchUnsplashTool)->handle(new Request(['query' => 'nature', 'order_by' => 'latest']));

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

        return $params['order_by'] === 'latest';
    });
});

test('returns mapped results on success', function () {
    $photo = require __DIR__.'/../Fixtures/unsplash_photo.php';

    Http::fake([
        'api.unsplash.com/*' => Http::response([
            'total' => 1,
            'total_pages' => 1,
            'results' => [$photo],
        ]),
    ]);

    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature']));
    $body = json_decode((string) $response->content(), true);

    expect($response->isError())->toBeFalse()
        ->and($body['total'])->toBe(1)
        ->and($body['results'][0]['id'])->toBe('abc123xyz')
        ->and($body['results'][0]['photographer'])->toBe('Jane Doe');
});

test('returns error response on API failure', function () {
    Http::fake(['*' => Http::response([], 500)]);

    $response = (new SearchUnsplashTool)->handle(new Request(['query' => 'nature']));

    expect($response->isError())->toBeTrue();
});
