<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp;

use JaapMoolenaar\UnsplashMcp\Tools\ImportPhotoTool;
use JaapMoolenaar\UnsplashMcp\Tools\SearchUnsplashTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('unsplash')]
#[Version('1.0.0')]
#[Instructions('Search and import Unsplash photos. Use `search-unsplash` to find photos, then `import-unsplash-photo` to download and store them. Pass the `registrar` parameter to choose the storage target (e.g. `statamic` for Statamic asset libraries).')]
class UnsplashServer extends Server
{
    protected array $tools = [
        SearchUnsplashTool::class,
        ImportPhotoTool::class,
    ];
}
