<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Http;

/**
 * Intentionally tiny V0 skeleton router. There are NO data endpoints yet.
 *
 * A future resource controller will run only behind the central transport,
 * authentication, resource-policy and validation pipeline. Plugins must not
 * register paths directly in this class, as that bypass would be unsafe.
 */
final class Router
{
    /** @return array{0: Response, 1: string} */
    public function dispatch(Request $request, string $requestId): array
    {
        if ($request->path !== '/api/v0/health') {
            return [Response::failure(404, 'not_found', 'Not found.', $requestId), 'not_found'];
        }

        if ($request->method !== 'GET') {
            return [
                new Response(
                    405,
                    ['error' => ['code' => 'method_not_allowed', 'message' => 'Method not allowed.'], 'requestId' => $requestId],
                    ['Allow' => 'GET'],
                ),
                'method_not_allowed',
            ];
        }

        return [Response::health(), 'health'];
    }
}
