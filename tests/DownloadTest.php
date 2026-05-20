<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Unsplash\Client;
use JaapMoolenaar\UnsplashMcp\Unsplash\Download;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\PhotoNotFound;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RequestFailed;

beforeEach(function () {
    Storage::fake('local');
});

test('downloads and stores the image to temp', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']);

    $result = (new Download(new Client, photoId: 'abc123xyz', baseName: 'my-photo'))->get();

    expect($result)->toBeInstanceOf(DownloadResult::class)
        ->and($result->fileName)->toBe('my-photo.jpg')
        ->and($result->tempPath)->toStartWith('unsplash-temp/')
        ->and($result->tempPath)->toEndWith('-my-photo.jpg')
        ->and($result->photo->photographer)->toBe('Jane Doe');

    Storage::disk('local')->assertExists($result->tempPath);
});

test('defaults baseName to unsplash-{photoId}', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200);

    $result = (new Download(new Client, photoId: 'abc123xyz'))->get();

    expect($result->fileName)->toBe('unsplash-abc123xyz.jpg')
        ->and($result->tempPath)->toStartWith('unsplash-temp/')
        ->and($result->tempPath)->toEndWith('-unsplash-abc123xyz.jpg');

    Storage::disk('local')->assertExists($result->tempPath);
});

test('throws PhotoNotFound when photo lookup fails', function () {
    Http::fake(['*' => Http::response([], 404)]);

    expect(fn () => (new Download(new Client, photoId: 'abc123xyz'))->get())
        ->toThrow(PhotoNotFound::class, 'abc123xyz');
});

test('throws RequestFailed when download_location is missing', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    unset($photo['links']['download_location']);

    Http::fakeSequence()->push($photo);

    expect(fn () => (new Download(new Client, photoId: 'abc123xyz'))->get())
        ->toThrow(RequestFailed::class, "missing 'links.download_location'");
});

test('throws RequestFailed when download event call fails', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push([], 500);

    expect(fn () => (new Download(new Client, photoId: 'abc123xyz'))->get())
        ->toThrow(RequestFailed::class);
});

test('throws RequestFailed when image download fails', function () {
    $photo = require __DIR__.'/Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push([], 503);

    expect(fn () => (new Download(new Client, photoId: 'abc123xyz'))->get())
        ->toThrow(RequestFailed::class);
});
