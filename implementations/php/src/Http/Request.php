<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Http;

use InvalidArgumentException;

/**
 * Small immutable normalized request value; does not trust proxy headers.
 *
 * This first slice does not parse payloads because no resource/data routes
 * are enabled. Route normalization is deliberately strict; we never turn an
 * HTTP parameter into a filesystem path or an SQL identifier.
 */
final readonly class Request
{
    public function __construct(public string $method, public string $path)
    {
    }

    /** @param array<string, mixed> $server @param array<string, mixed> $query */
    public static function fromGlobals(array $server, array $query): self
    {
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $server['REQUEST_URI'] ?? '/';

        if (!is_string($method) || !is_string($uri) || strlen($uri) > 2048) {
            throw new InvalidArgumentException('Invalid HTTP request line.');
        }

        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            throw new InvalidArgumentException('Invalid HTTP path.');
        }

        if (array_key_exists('route', $query)) {
            // The fallback is allowed *only* at the entry point; an ambiguous
            // route parameter on a different URL is never used as an override.
            if ($path !== '/index.php' && $path !== '/') {
                throw new InvalidArgumentException('Ambiguous routing source.');
            }
            $path = $query['route'];
        }

        if (!is_string($path)
            || strlen($path) > 512
            || preg_match('/^\/[a-zA-Z0-9_\/-]*$/D', $path) !== 1
            || str_contains($path, '//')
            || str_contains($path, '..')
        ) {
            throw new InvalidArgumentException('Invalid HTTP route.');
        }

        return new self(strtoupper($method), $path);
    }
}
