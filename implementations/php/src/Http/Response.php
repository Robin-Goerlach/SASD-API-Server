<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Http;

/**
 * Minimal JSON response without secrets, stack traces or host metadata.
 * The public error envelope intentionally follows the documented V0 design.
 */
final readonly class Response
{
    /** @param array<string, mixed> $body @param array<string, string> $headers */
    public function __construct(public int $status, public array $body, public array $headers = [])
    {
    }

    public static function health(): self
    {
        return new self(200, ['data' => ['status' => 'ok']]);
    }

    public static function failure(int $status, string $code, string $message, string $requestId): self
    {
        return new self($status, [
            'error' => ['code' => $code, 'message' => $message],
            'requestId' => $requestId,
        ]);
    }

    public function send(string $requestId): void
    {
        // Avoid advertising the PHP version on the public health endpoint.
        header_remove('X-Powered-By');
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('X-Request-ID: ' . $requestId);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
