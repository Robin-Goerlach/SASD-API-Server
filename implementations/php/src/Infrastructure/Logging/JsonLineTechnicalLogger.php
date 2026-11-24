<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Infrastructure\Logging;

use RuntimeException;
use Sasd\ApiServer\Contracts\TechnicalLoggerInterface;

/**
 * Private UTC JSONL request log, with a strict low-sensitivity schema.
 *
 * Do not accept arbitrary structured request contexts here. In particular,
 * client IPs, URL queries, request bodies, headers, credentials, SQL bindings
 * and exception messages must never be copied to this log implicitly.
 *
 * Log path must be a private absolute filesystem path, OUTSIDE the publicly
 * served directory. A write failure raises rather than silently disabling
 * request logging. Rotation and retention are operator responsibilities.
 */
final class JsonLineTechnicalLogger implements TechnicalLoggerInterface
{
    private readonly string $path;

    public function __construct(string $path, string $documentRoot)
    {
        $public = realpath($documentRoot);
        $parent = $path !== '' ? realpath(dirname($path)) : false;

        if ($public === false || $parent === false || !str_starts_with($path, DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Private technical log path is unavailable.');
        }

        if ($parent === $public || str_starts_with($parent, $public . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Technical log directory is exposed to HTTP.');
        }

        // Reject an already-existing symlink pointing into a public location.
        $existing = file_exists($path) ? realpath($path) : false;
        if ($existing !== false && ($existing === $public || str_starts_with($existing, $public . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Technical log file is exposed to HTTP.');
        }

        if (is_link($path) || (file_exists($path) && !is_file($path))) {
            throw new RuntimeException('Technical log target must be a regular file.');
        }

        $this->path = $parent . DIRECTORY_SEPARATOR . basename($path);
    }

    public function record(string $requestId, string $method, int $status, string $event, int $durationMs): void
    {
        $knownEvents = ['health', 'not_found', 'method_not_allowed', 'invalid_request', 'internal_error'];

        // Only bounded server-defined fields leave the request processing code.
        $item = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'request_id' => preg_match('/^[0-9a-f]{32}$/D', $requestId) === 1 ? $requestId : 'invalid',
            'method' => preg_match('/^[A-Z]{1,12}$/D', $method) === 1 ? $method : 'INVALID',
            'status' => $status,
            'event' => in_array($event, $knownEvents, true) ? $event : 'internal_error',
            'duration_ms' => max(0, $durationMs),
        ];

        $line = json_encode($item, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n";

        // Newly created log files default to 0600 even when the system umask
        // is permissive. Existing file permissions need an operator audit.
        $previousUmask = umask(0077);
        try {
            $handle = @fopen($this->path, 'ab');
        } finally {
            umask($previousUmask);
        }

        if ($handle === false) {
            throw new RuntimeException('Protected request log cannot be opened.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Protected request log lock failed.');
            }

            if (fwrite($handle, $line) !== strlen($line) || !fflush($handle)) {
                throw new RuntimeException('Protected request log write failed.');
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
