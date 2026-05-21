<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use JaapMoolenaar\UnsplashMcp\Unsplash\DTO\SearchResult;
use JaapMoolenaar\UnsplashMcp\Unsplash\Search;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Throwable;

#[Name('search-unsplash')]
#[Description('
Search Unsplash for photos by keyword.
Returns a list of results, each with an `id`, image URLs, description, and photographer attribution.
Results are returned per page, use `page` and `per_page` to go through pages.
Pass the `id` of a chosen result to the `import-unsplash-photo` tool to download and store it.
')]
#[IsOpenWorld(true)]
class SearchUnsplashTool extends Tool
{
    public function handle(Request $request): Response
    {
        try {
            $validated = $this->validateRequest($request);

            $query = $validated['query'];
            $page = (int) ($validated['page'] ?? 1);
            $perPage = (int) ($validated['per_page'] ?? 10);
            $orientation = $validated['orientation'] ?? null;
            $orderBy = $validated['order_by'] ?? 'relevant';

            $result = app()->make(Search::class, [
                'query' => $query,
                'page' => $page,
                'perPage' => $perPage,
                'orderBy' => $orderBy,
                'orientation' => $orientation,
            ])->get();

            $photos = $result->photos->map(fn (SearchResult $photo) => [
                'id' => $photo->id,
                'description' => $photo->description,
                'alt_description' => $photo->altDescription,
                'urls' => [
                    'regular' => $photo->regularUrl,
                    'small' => $photo->smallUrl,
                    'thumb' => $photo->thumbUrl,
                ],
                'link' => $photo->htmlLink,
                'photographer' => $photo->photographer,
                'photographer_url' => $photo->photographerUrl,
            ])->all();

            return Response::json([
                'total' => $result->total,
                'total_pages' => $result->totalPages,
                'results' => $photos,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return Response::error(sprintf('[%s] %s', class_basename($exception), $exception->getMessage()));
        }
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'query' => 'required|string|max:50',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:30',
            'order_by' => 'in:relevant,latest',
            'orientation' => 'in:landscape,portrait,squarish',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The search keyword or phrase. (Max. 50 characters)')
                ->required(),
            'page' => $schema->integer()
                ->description('The page number to start on. Defaults to 1.'),
            'per_page' => $schema->integer()
                ->description('Number of photos to return per page (1–30). Defaults to 10.'),
            'order_by' => $schema->string()
                ->description('Order results by: relevant, or latest.'),
            'orientation' => $schema->string()
                ->description('Filter by orientation: landscape, portrait, or squarish.'),
        ];
    }
}
