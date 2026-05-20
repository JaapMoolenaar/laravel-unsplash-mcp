<?php

use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Registrar\StatamicRegistrar;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\PhotoResult;
use Statamic\Facades\AssetContainer;

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

describe('without Statamic installed', function () {
    // This describe block must run before fakeStatamic() is ever called in this process.
    test('throws RuntimeException when Statamic is not installed', function () {
        $registrar = new StatamicRegistrar;

        expect(fn () => $registrar->register($this->result))
            ->toThrow(RuntimeException::class, 'not installed');
    });
})->skip(
    fn () => class_exists(AssetContainer::class),
    'This test requires Statamic to not exist and to run in deterministic order. (Possibly, re-run without --random-order).',
);

describe('with Statamic installed', function () {
    beforeEach(fn () => fakeStatamic());

    test('throws RuntimeException when container is not found', function () {
        AssetContainer::shouldReceive('find')->with('assets')->andReturnNull();

        expect(fn () => (new StatamicRegistrar)->register($this->result))
            ->toThrow(RuntimeException::class, "Statamic asset container 'assets' not found.");
    });

    test('moves file and saves asset with Unsplash metadata', function () {
        $mockAsset = Mockery::mock();
        $mockAsset->shouldReceive('merge')
            ->once()
            ->with([
                'unsplash_id' => 'abc123xyz',
                'unsplash_photographer' => 'Jane Doe',
                'unsplash_photographer_url' => 'https://unsplash.com/@janedoe',
                'unsplash_photo_url' => 'https://unsplash.com/photos/abc123xyz',
                'unsplash_photo_cdn_url' => 'https://images.unsplash.com/photo-abc123',
            ])
            ->andReturnSelf();
        $mockAsset->shouldReceive('save')->once();

        $mockContainer = Mockery::mock();
        $mockContainer->shouldReceive('diskHandle')->andReturn('public');
        $mockContainer->shouldReceive('makeAsset')->with('my-photo.jpg')->andReturn($mockAsset);

        AssetContainer::shouldReceive('find')->with('assets')->andReturn($mockContainer);

        $registered = (new StatamicRegistrar)->register($this->result);

        expect($registered->fileName)->toBe('my-photo.jpg');
        Storage::disk('public')->assertExists('my-photo.jpg');
        Storage::disk('local')->assertMissing('unsplash-temp/my-photo.jpg');
    });

    test('deletes file from final disk when asset save fails', function () {
        $mockAsset = Mockery::mock();
        $mockAsset->shouldReceive('merge')->andReturnSelf();
        $mockAsset->shouldReceive('save')->andThrow(new RuntimeException('save failed'));

        $mockContainer = Mockery::mock();
        $mockContainer->shouldReceive('diskHandle')->andReturn('public');
        $mockContainer->shouldReceive('makeAsset')->andReturn($mockAsset);

        AssetContainer::shouldReceive('find')->with('assets')->andReturn($mockContainer);

        expect(fn () => (new StatamicRegistrar)->register($this->result))
            ->toThrow(RuntimeException::class, 'save failed');

        Storage::disk('public')->assertMissing('my-photo.jpg');
    });

    test('uses statamic_container config for the container handle', function () {
        config(['unsplash-mcp.statamic.asset_container' => 'media']);

        AssetContainer::shouldReceive('find')->with('media')->andReturnNull();

        expect(fn () => (new StatamicRegistrar)->register($this->result))
            ->toThrow(RuntimeException::class, "Statamic asset container 'media' not found.");
    });
});
