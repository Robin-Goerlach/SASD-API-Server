<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Config;

use RuntimeException;

/**
 * Strict parser for a non-executable, operator-owned JSON plugin manifest.
 *
 * A missing file, incorrect schema, invalid type, unknown key or an attempt
 * to place configuration inside the public document root is a startup error.
 * The caller must map it to a generic public 503; details belong to a
 * protected operator diagnostic channel, never to a client response.
 */
final class PluginConfigLoader
{
    /** @return list<string> */
    public function load(string $path, string $documentRoot): array
    {
        $realPath = $path !== '' ? realpath($path) : false;
        $webRoot = realpath($documentRoot);

        if ($realPath === false || $webRoot === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException('Private plugin configuration cannot be read.');
        }

        if ($realPath === $webRoot || str_starts_with($realPath, $webRoot . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Private plugin configuration is inside the webroot.');
        }

        $bytes = @file_get_contents($realPath);
        if ($bytes === false || strlen($bytes) > 16384) {
            throw new RuntimeException('Invalid plugin configuration size or read failure.');
        }

        try {
            $root = json_decode($bytes, false, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Invalid JSON plugin configuration.', 0, $exception);
        }

        $keys = is_object($root) ? array_keys(get_object_vars($root)) : [];
        sort($keys);

        if (!is_object($root)
            || $keys !== ['enabled', 'schemaVersion']
            || !is_int($root->schemaVersion)
            || $root->schemaVersion !== 1
            || !is_array($root->enabled)
        ) {
            throw new RuntimeException('Unsupported plugin configuration schema.');
        }

        // Objects, nested lists and numeric IDs are not accepted as plugin IDs.
        foreach ($root->enabled as $id) {
            if (!is_string($id)) {
                throw new RuntimeException('Invalid plugin configuration selection.');
            }
        }

        return $root->enabled;
    }
}
