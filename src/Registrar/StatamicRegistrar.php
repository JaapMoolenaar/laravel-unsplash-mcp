<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Registrar;

use Illuminate\Support\Facades\Storage;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\DownloadResult;
use RuntimeException;
use Statamic\Facades\AssetContainer;
use Throwable;

class StatamicRegistrar extends AbstractRegistrar
{
    public function register(DownloadResult $result): RegisteredResult
    {
        $this->throwIfAssetContainerIsUnavailable();

        $handle = config('unsplash-mcp.statamic.asset_container', 'assets');

        $container = AssetContainer::find($handle);

        if (! $container) {
            throw new RuntimeException("Statamic asset container '{$handle}' not found.");
        }

        $registered = $this->moveFromTemp($result, $container->diskHandle());

        try {
            $container->makeAsset($registered->fileName)
                ->merge([
                    'unsplash_id' => $result->photo->id,
                    'unsplash_photographer' => $result->photo->photographer,
                    'unsplash_photographer_url' => $result->photo->photographerUrl,
                    'unsplash_photo_url' => $result->photo->htmlLink,
                    'unsplash_photo_cdn_url' => $result->photo->rawUrl,
                ])
                ->save();
        } catch (Throwable $e) {
            Storage::disk($container->diskHandle())->delete($registered->fileName);

            throw $e;
        }

        return $registered;
    }

    private function throwIfAssetContainerIsUnavailable(): void
    {
        if (! class_exists(AssetContainer::class)) {
            throw new RuntimeException('Statamic asset container is not available, seems like Statamic is not installed.');
        }
    }
}
