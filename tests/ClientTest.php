<?php

use Illuminate\Support\Facades\Http;
use JaapMoolenaar\UnsplashMcp\Unsplash\Client;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\AccessTokenMissing;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\AuthenticationFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RateLimitExceeded;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RequestFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\ResourceNotFound;

test('throws AccessTokenMissing when access key is not configured', function () {
    config()->set('unsplash-mcp.access_key', null);

    expect(fn () => app()->make(Client::class)->get('https://api.unsplash.com/photos/abc'))
        ->toThrow(AccessTokenMissing::class);
});

test('sends requests with Client-ID authorization header', function () {
    Http::fake(['*' => Http::response([], 200)]);

    app()->make(Client::class)->get('https://api.unsplash.com/photos/abc');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Client-ID test-key'));
});

test('throws AuthenticationFailed on 401 response', function () {
    Http::fake(['*' => Http::response([], 401)]);

    expect(fn () => app()->make(Client::class)->get('https://api.unsplash.com/photos/abc'))
        ->toThrow(AuthenticationFailed::class);
});

test('throws ResourceNotFound on 404 response', function () {
    Http::fake(['*' => Http::response([], 404)]);

    expect(fn () => app()->make(Client::class)->get('https://api.unsplash.com/photos/abc'))
        ->toThrow(ResourceNotFound::class);
});

test('throws RateLimitExceeded on 429 response', function () {
    Http::fake(['*' => Http::response([], 429)]);

    expect(fn () => app()->make(Client::class)->get('https://api.unsplash.com/photos/abc'))
        ->toThrow(RateLimitExceeded::class);
});

test('throws RequestFailed on other error responses', function () {
    Http::fake(['*' => Http::response([], 500)]);

    expect(fn () => app()->make(Client::class)->get('https://api.unsplash.com/photos/abc'))
        ->toThrow(RequestFailed::class);
});
