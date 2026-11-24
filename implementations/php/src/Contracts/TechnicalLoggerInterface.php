<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Contracts;

/**
 * Mandatory, minimal bootstrap logging boundary.
 *
 * This interface remains owned by the core even after a future logging
 * plugin exists. The core must still record plugin boot failures and cannot
 * make security visibility depend on a remotely hosted LogSink service.
 */
interface TechnicalLoggerInterface
{
    public function record(string $requestId, string $method, int $status, string $event, int $durationMs): void;
}
