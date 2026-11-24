<?php

declare(strict_types=1);

namespace Sasd\ApiServer;

use InvalidArgumentException;
use Sasd\ApiServer\Config\PluginConfigLoader;
use Sasd\ApiServer\Contracts\PluginFactoryInterface;
use Sasd\ApiServer\Contracts\TechnicalLoggerInterface;
use Sasd\ApiServer\Core\Plugin\PluginManager;
use Sasd\ApiServer\Http\Request;
use Sasd\ApiServer\Http\Response;
use Sasd\ApiServer\Http\Router;
use Sasd\ApiServer\Infrastructure\Logging\JsonLineTechnicalLogger;
use Throwable;

/**
 * Per-request composition root for shared-hosting PHP 8.4.
 *
 * Factories are assembled in trusted source code; the JSON file only
 * selects which pre-reviewed IDs to enable. No external service is required
 * to boot. This is intentionally a small kernel, NOT a completed CRUD API.
 */
final class Bootstrap
{
    private function __construct(
        private readonly TechnicalLoggerInterface $logger,
        private readonly Router $router,
        private readonly PluginManager $plugins,
    ) {
    }

    /**
     * @param list<PluginFactoryInterface> $trustedFactories
     * @throws Throwable Invalid configuration or failed plugin initialization.
     */
    public static function create(
        string $configPath,
        string $logPath,
        string $documentRoot,
        array $trustedFactories = [],
    ): self {
        // Native technical logging is a core bootstrap responsibility.
        // Even a future optional logging plugin cannot replace this failure
        // channel; otherwise a failed logging plugin could become invisible.
        $logger = new JsonLineTechnicalLogger($logPath, $documentRoot);
        $enabled = (new PluginConfigLoader())->load($configPath, $documentRoot);
        $manager = new PluginManager();

        foreach ($trustedFactories as $factory) {
            if (!$factory instanceof PluginFactoryInterface) {
                throw new InvalidArgumentException('Untrusted plugin factory argument.');
            }
            $manager->register($factory);
        }

        $manager->start($enabled);
        return new self($logger, new Router(), $manager);
    }

    /** @param array<string, mixed> $server @param array<string, mixed> $query */
    public function handle(array $server, array $query, string $requestId): Response
    {
        $begin = hrtime(true);
        $method = is_string($server['REQUEST_METHOD'] ?? null) ? strtoupper($server['REQUEST_METHOD']) : 'INVALID';
        $event = 'internal_error';

        try {
            try {
                $request = Request::fromGlobals($server, $query);
                [$response, $event] = $this->router->dispatch($request, $requestId);
            } catch (InvalidArgumentException) {
                $event = 'invalid_request';
                $response = Response::failure(400, 'invalid_request', 'Invalid request.', $requestId);
            }
        } catch (Throwable) {
            // No exception text or stack trace reaches HTTP or the safe log.
            $response = Response::failure(500, 'internal_error', 'Internal server error.', $requestId);
        }

        $duration = max(0, (int) ((hrtime(true) - $begin) / 1_000_000));
        try {
            $this->logger->record($requestId, $method, $response->status, $event, $duration);
        } catch (Throwable) {
            // Emergency fallback is deliberately generic; never log the path
            // or the underlying exception, which may contain credentials.
            error_log('SASD API Server: protected request log unavailable');
            // If the protected log cannot be written, never claim success.
            return Response::failure(503, 'unavailable', 'Service unavailable.', $requestId);
        }

        return $response;
    }

    /** @return list<string> */
    public function activePluginIds(): array
    {
        return $this->plugins->activeIds();
    }
}
