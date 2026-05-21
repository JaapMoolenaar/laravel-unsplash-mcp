# Unsplash MCP for Laravel

A Laravel package that exposes Unsplash photo search and import as an [MCP](https://github.com/laravel/mcp) (Model Context Protocol) server. Designed for use with Statamic: an AI assistant can search Unsplash, pick a photo, and download it directly into the Statamic asset library — including attribution metadata.

## Requirements

- PHP 8.4+
- Laravel 13+
- [`laravel/mcp`](https://github.com/laravel/mcp) ≥ 0.1

## Installation

```bash
composer require jaapmoolenaar.nl/laravel-unsplash-mcp
```

The service provider is auto-discovered and registers the MCP server automatically.

## Configuration

Add your Unsplash API access key to your `.env`:

```env
UNSPLASH_ACCESS_KEY=your-unsplash-access-key
```

You can get an access key by creating an application at [unsplash.com/developers](https://unsplash.com/developers).

Optionally publish the config file if you need to customise it:

```bash
php artisan vendor:publish --tag=unsplash-mcp-config
```

### All environment variables

| Variable                      | Default    | Description                                                    |
|-------------------------------|------------|----------------------------------------------------------------|
| `UNSPLASH_ACCESS_KEY`         | —          | Your Unsplash API access key (required)                        |
| `UNSPLASH_REGISTRAR`          | `disk`     | Default registrar used by `import-unsplash-photo`              |
| `UNSPLASH_TEMP_DISK`          | `local`    | Temporary disk used while downloading before moving            |
| `UNSPLASH_DISK`               | `public`   | Target disk for the `disk` registrar                           |
| `UNSPLASH_STATAMIC_CONTAINER` | `assets`   | Asset container handle for the `statamic` registrar            |

## MCP Server

The package registers a local MCP server named `unsplash` with two tools:

### `search-unsplash`

Search Unsplash for photos by keyword. Returns paginated results with photo IDs, image URLs, descriptions, and photographer attribution.

| Parameter     | Type    | Required | Description                                      |
|---------------|---------|----------|--------------------------------------------------|
| `query`       | string  | Yes      | Search keyword or phrase (max 50 characters)     |
| `page`        | integer | No       | Page number (minimum 1, default 1)               |
| `per_page`    | integer | No       | Results per page (maximum 30, default 10)        |
| `order_by`    | string  | No       | Sort by `relevant` (default) or `latest`         |
| `orientation` | string  | No       | Filter by `landscape`, `portrait`, or `squarish` |

Example response:

```json
{
    "total": 1420,
    "total_pages": 142,
    "results": [
        {
            "id": "abc123xyz",
            "description": "A beautiful nature photo",
            "alt_description": "green trees in a forest",
            "urls": {
                "regular": "https://images.unsplash.com/photo-abc123?w=1080",
                "small": "https://images.unsplash.com/photo-abc123?w=400",
                "thumb": "https://images.unsplash.com/photo-abc123?w=200"
            },
            "link": "https://unsplash.com/photos/abc123xyz",
            "photographer": "Jane Doe",
            "photographer_url": "https://unsplash.com/@janedoe"
        }
    ]
}
```

### `import-unsplash-photo`

Downloads a photo (by ID from `search-unsplash`) and stores it using the configured registrar. Use the `registrar` parameter to control where the photo ends up.

| Parameter   | Type   | Required | Description                                                      |
|-------------|--------|----------|------------------------------------------------------------------|
| `photo_id`  | string | Yes      | The Unsplash photo ID (from `search-unsplash` results)           |
| `basename`  | string | No       | Base filename without extension (default: `unsplash-{photo_id}`) |
| `registrar` | string | No       | Storage target: `disk` or `statamic` (default: `UNSPLASH_REGISTRAR`) |

Example response:

```json
{
    "filename": "my-photo.jpg",
    "url": "https://example.com/storage/my-photo.jpg",
    "photographer": "Jane Doe"
}
```

> **Note:** The download flow follows the [Unsplash API guidelines](https://help.unsplash.com/en/articles/2511258-guideline-triggering-a-download) by triggering the required download event before saving the image.

#### Registrars

**`disk`** — stores the photo on the Laravel disk configured by `UNSPLASH_DISK` (defaults to `public`).

**`statamic`** — stores the photo in the Statamic asset container configured by `UNSPLASH_STATAMIC_CONTAINER` (defaults to `assets`) and writes Unsplash attribution as asset metadata:

```yaml
unsplash_id: abc123xyz
unsplash_photographer: Jane Doe
unsplash_photographer_url: https://unsplash.com/@janedoe
unsplash_photo_url: https://unsplash.com/photos/abc123xyz
unsplash_photo_cdn_url: https://images.unsplash.com/photo-abc123
```

Requires `statamic/cms` to be installed.

## Connecting an MCP client

The service provider registers the MCP server automatically. How you connect an AI client depends on the transport.

### Stdio: desktop AI tools (recommended)

Desktop tools like Claude Code, Cursor, and VS Code connect to MCP servers over stdio. It should start the server with:

```bash
php artisan mcp:start unsplash
```

You can configure your client to run that command.

For **Claude Code**, add this to `.mcp.json` in your project root:

```json
{
    "mcpServers": {
        "unsplash": {
            "type": "stdio",
            "command": "php",
            "args": ["artisan", "mcp:start", "unsplash"]
        }
    }
}
```

### HTTP: web-based clients

If your client connects over HTTP, publish the AI routes file and register the server:

```bash
php artisan vendor:publish --tag=ai-routes
```

Then add to `routes/ai.php`:

```php
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/unsplash', \JaapMoolenaar\UnsplashMcp\UnsplashServer::class);
```

The server is then reachable at `POST /mcp/unsplash`.

## Usage with an AI assistant

Once the MCP server is active, an AI assistant (e.g. Claude) connected to your Laravel app can:

1. Call `search-unsplash` to find photos matching a description.
2. Pick a result from the list.
3. Call `import-unsplash-photo` with the chosen `id` — pass `registrar=statamic` to import directly into the Statamic asset library.
4. Tell you the filename — open the Statamic CP, go to Assets, and select it for the desired field (e.g. a hero image).

## Development

### Running the tests

```bash
composer test
```

Tests use [Pest](https://pestphp.com/) and [Orchestra Testbench](https://github.com/orchestral/testbench). HTTP calls are intercepted with `Http::fake()` and storage is isolated with `Storage::fake('public')`.

### Code style

This package uses [Laravel Pint](https://github.com/laravel/pint) with the `laravel` preset.

```bash
composer lint        # fix issues
composer lint:check  # dry-run (useful for CI)
```
