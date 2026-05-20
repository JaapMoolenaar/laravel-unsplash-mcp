<?php

use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\SearchResult;

test('fromUnsplashData maps all fields correctly', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    $result = SearchResult::fromUnsplashData($photo);

    expect($result->id)->toBe('abc123xyz')
        ->and($result->slug)->toBe('beautiful-nature-abc123xyz')
        ->and($result->width)->toBe(4000)
        ->and($result->height)->toBe(3000)
        ->and($result->color)->toBe('#8B7355')
        ->and($result->description)->toBe('A beautiful nature photo')
        ->and($result->altDescription)->toBe('green trees in a forest')
        ->and($result->rawUrl)->toBe('https://images.unsplash.com/photo-abc123')
        ->and($result->regularUrl)->toBe('https://images.unsplash.com/photo-abc123?w=1080')
        ->and($result->smallUrl)->toBe('https://images.unsplash.com/photo-abc123?w=400')
        ->and($result->thumbUrl)->toBe('https://images.unsplash.com/photo-abc123?w=200')
        ->and($result->htmlLink)->toBe('https://unsplash.com/photos/abc123xyz')
        ->and($result->downloadLink)->toBe('https://unsplash.com/photos/abc123xyz/download')
        ->and($result->downloadLocationLink)->toBe('https://api.unsplash.com/photos/abc123xyz/download')
        ->and($result->photographer)->toBe('Jane Doe')
        ->and($result->photographerUrl)->toBe('https://unsplash.com/@janedoe');
});

test('fromUnsplashData handles null description and alt_description', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';
    $photo['description'] = null;
    $photo['alt_description'] = null;

    $result = SearchResult::fromUnsplashData($photo);

    expect($result->description)->toBeNull()
        ->and($result->altDescription)->toBeNull();
});
