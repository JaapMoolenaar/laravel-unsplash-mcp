<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash;

use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\PhotoResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\PhotoNotFound;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RequestFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\ResourceNotFound;
use Throwable;

class Download
{
    public readonly string $baseName;

    public readonly string $fileName;

    public function __construct(
        private readonly Client $client,
        public readonly string $photoId,
        ?string $baseName = null,
        ?string $fileName = null,
    ) {
        $this->baseName = $baseName ?: "unsplash-{$photoId}";
        $this->fileName = $fileName ?: "{$this->baseName}.jpg";
    }

    public function get(): DownloadResult
    {
        try {
            $photoResponse = $this->client->get("https://api.unsplash.com/photos/{$this->photoId}");
        } catch (ResourceNotFound) {
            throw new PhotoNotFound("Photo '{$this->photoId}' was not found on Unsplash.");
        }

        $downloadLocation = $photoResponse->json('links.download_location');

        if (! $downloadLocation) {
            throw new RequestFailed(
                "Unsplash API response for photo '{$this->photoId}' is missing 'links.download_location'."
            );
        }

        // Required by Unsplash TOS: trigger the download event before saving the image.
        $downloadResponse = $this->client->get($downloadLocation);

        $downloadUrl = $downloadResponse->json('url');

        if (empty($downloadUrl)) {
            throw new RequestFailed('Unsplash download-event response is missing the image URL.');
        }

        $imageResponse = $this->client->get($downloadUrl);

        $tempPath = 'unsplash-temp/'.str()->uuid().'-'.$this->fileName;

        Storage::disk(config('unsplash-mcp.temp_disk'))->put($tempPath, $imageResponse->toPsrResponse()->getBody());

        try {
            $photo = PhotoResult::fromUnsplashData($photoResponse->json());
        } catch (Throwable $e) {
            Storage::disk(config('unsplash-mcp.temp_disk'))->delete($tempPath);

            throw $e;
        }

        return new DownloadResult(
            tempPath: $tempPath,
            fileName: $this->fileName,
            photo: $photo,
        );
    }
}
