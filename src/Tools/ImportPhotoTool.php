<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JaapMoolenaar\UnsplashMcp\Unsplash\Download;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Throwable;

#[Name('import-unsplash-photo')]
#[Description('Download an Unsplash photo (found via `search-unsplash`) and store it using the configured registrar. Use the `registrar` parameter to choose where the photo is stored.')]
#[IsOpenWorld(true)]
class ImportPhotoTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $validated = $this->validateRequest($request);

            $photoId = $validated['photo_id'];
            $baseName = $validated['basename'] ?? null;
            $registrar = $validated['registrar'] ?? config('unsplash-mcp.registrar', 'disk');

            $registrarFqn = config("unsplash-mcp.registrars.{$registrar}");

            if (! $registrarFqn) {
                return Response::error("Unknown registrar '{$registrar}'. Available: {$this->availableRegistrars()}.");
            }

            $result = app()->make(Download::class, [
                'photoId' => $photoId,
                'baseName' => $baseName,
            ])->get();

            $registered = app()->make($registrarFqn)->register($result);

            return Response::json([
                'filename' => $registered->fileName,
                'url' => $registered->url,
                'photographer' => $result->photo->photographer,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return Response::error(sprintf('[%s] %s', class_basename($exception), $exception->getMessage()));
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'photo_id' => 'required|string',
            'basename' => 'string',
            'registrar' => 'in:'.$this->availableRegistrars(','),
        ]);
    }

    private function availableRegistrars(string $join = ', '): string
    {
        return implode($join, array_keys(config('unsplash-mcp.registrars', [])));
    }

    public function schema(JsonSchema $schema): array
    {
        $default = config('unsplash-mcp.registrar', 'disk');

        return [
            'photo_id' => $schema->string()
                ->description('The Unsplash photo ID (from search-unsplash results).')
                ->required(),
            'basename' => $schema->string()
                ->description('Base filename without extension. Defaults to unsplash-{photo_id}.'),
            'registrar' => $schema->string()
                ->description("Storage registrar. Available: {$this->availableRegistrars()}. Defaults to '{$default}'."),
        ];
    }
}
