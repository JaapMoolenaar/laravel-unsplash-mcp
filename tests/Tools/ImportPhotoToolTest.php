<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Registrar\RegisteredResult;
use JaapMoolenaar\UnsplashMcp\Registrar\StatamicRegistrar;
use JaapMoolenaar\UnsplashMcp\Tools\ImportPhotoTool;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use Laravel\Mcp\Request;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

test('returns filename, url and photographer on success', function () {
    $photo = require __DIR__.'/../Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200);

    $response = (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
        'basename' => 'my-photo',
    ]));

    $body = json_decode((string) $response->content(), true);

    expect($response->isError())->toBeFalse()
        ->and($body['filename'])->toBe('my-photo.jpg')
        ->and($body['photographer'])->toBe('Jane Doe');
});

test('defaults filename to unsplash-{photo_id}', function () {
    $photo = require __DIR__.'/../Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200);

    $response = (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
    ]));

    $body = json_decode((string) $response->content(), true);

    expect($body['filename'])->toBe('unsplash-abc123xyz.jpg');
});

test('moves file to configured disk and cleans up temp', function () {
    $photo = require __DIR__.'/../Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200);

    (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
        'basename' => 'my-photo',
    ]));

    Storage::disk('public')->assertExists('my-photo.jpg');
    Storage::disk('local')->assertMissing('unsplash-temp/my-photo.jpg');
});

test('uses statamic registrar when registrar=statamic', function () {
    $photo = require __DIR__.'/../Fixtures/unsplash_photo.php';

    Http::fakeSequence()
        ->push($photo)
        ->push(['url' => 'https://images.unsplash.com/photo-abc123?dl=1'])
        ->push('fake-image-bytes', 200);

    $registrar = Mockery::mock(StatamicRegistrar::class);
    $registrar->shouldReceive('register')
        ->once()
        ->withArgs(fn (DownloadResult $result) => $result->fileName === 'my-photo.jpg')
        ->andReturn(new RegisteredResult('my-photo.jpg', 'https://example.com/my-photo.jpg'));
    app()->instance(StatamicRegistrar::class, $registrar);

    $response = (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
        'basename' => 'my-photo',
        'registrar' => 'statamic',
    ]));

    expect($response->isError())->toBeFalse();
});

test('returns error when photo_id is missing', function () {
    $response = (new ImportPhotoTool)->handle(new Request([]));

    expect($response->isError())->toBeTrue();
});

test('returns error for unknown registrar key', function () {
    $response = (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
        'registrar' => 'unknown',
    ]));

    expect($response->isError())->toBeTrue();
});

test('returns error response on download failure', function () {
    Http::fake(['*' => Http::response([], 404)]);

    $response = (new ImportPhotoTool)->handle(new Request([
        'photo_id' => 'abc123xyz',
    ]));

    expect($response->isError())->toBeTrue();
});
