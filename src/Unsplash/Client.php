<?php

declare(strict_types=1);

namespace JaapMoolenaar\UnsplashMcp\Unsplash;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\ForwardsCalls;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\AccessTokenMissing;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\AuthenticationFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RateLimitExceeded;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\RequestFailed;
use JaapMoolenaar\UnsplashMcp\Unsplash\Exceptions\ResourceNotFound;

/**
 * @mixin PendingRequest
 */
class Client
{
    use ForwardsCalls;

    /**
     * @throws AccessTokenMissing
     */
    private function accessKey(): string
    {
        $accessKey = config('unsplash-mcp.access_key');

        if (empty($accessKey)) {
            throw new AccessTokenMissing('Unsplash access key is not configured.');
        }

        return $accessKey;
    }

    /**
     * @throws AccessTokenMissing
     */
    private function pendingRequest(): PendingRequest
    {
        return Http::withToken($this->accessKey(), 'Client-ID')
            ->afterResponse(function (Response $response) {
                if (! $response->failed()) {
                    return;
                }

                match ($response->status()) {
                    401 => throw new AuthenticationFailed(
                        'Unsplash API authentication failed. Check that UNSPLASH_ACCESS_KEY is set and valid.'
                    ),
                    404 => throw new ResourceNotFound(
                        'The requested Unsplash resource was not found.'
                    ),
                    429 => throw new RateLimitExceeded(
                        'Unsplash API rate limit exceeded. Wait before retrying, or check your plan limits.'
                    ),
                    default => throw new RequestFailed(
                        "Unsplash API request failed with status {$response->status()}."
                    ),
                };
            });
    }

    /**
     * @throws AccessTokenMissing
     * @throws AuthenticationFailed
     * @throws ResourceNotFound
     * @throws RateLimitExceeded
     * @throws RequestFailed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardDecoratedCallTo($this->pendingRequest(), $method, $parameters);
    }
}
