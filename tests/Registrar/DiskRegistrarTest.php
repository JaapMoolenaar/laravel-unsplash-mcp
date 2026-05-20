<?php

use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Registrar\DiskRegistrar;
use JaapMoolenaar\UnsplashMcp\Registrar\RegisteredResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\PhotoResult;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    Storage::disk('local')->put('unsplash-temp/my-photo.jpg', 'fake-image-bytes');

    $photo = PhotoResult::fromUnsplashData(require __DIR__.'/../Fixtures/unsplash_photo.php');
    $this->result = new DownloadResult(
        tempPath: 'unsplash-temp/my-photo.jpg',
        fileName: 'my-photo.jpg',
        photo: $photo,
    );
});

test('moves file from temp to the configured disk', function () {
    (new DiskRegistrar)->register($this->result);

    Storage::disk('public')->assertExists('my-photo.jpg');
});

test('deletes the temp file after moving', function () {
    (new DiskRegistrar)->register($this->result);

    Storage::disk('local')->assertMissing('unsplash-temp/my-photo.jpg');
});

test('returns RegisteredResult with the correct filename and URL', function () {
    $registered = (new DiskRegistrar)->register($this->result);

    expect($registered)->toBeInstanceOf(RegisteredResult::class)
        ->and($registered->fileName)->toBe('my-photo.jpg')
        ->and($registered->url)->toBe(Storage::disk('public')->url('my-photo.jpg'));
});

test('uses the disk name from config', function () {
    Storage::fake('s3');
    config(['unsplash-mcp.disk.name' => 's3']);

    (new DiskRegistrar)->register($this->result);

    Storage::disk('s3')->assertExists('my-photo.jpg');
    Storage::disk('public')->assertMissing('my-photo.jpg');
});
