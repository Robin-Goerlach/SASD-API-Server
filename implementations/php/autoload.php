<?php

declare(strict_types=1);

/**
 * Tiny, intentionally dependency-free PSR-4-compatible class loader.
 *
 * Only the project's own namespace is resolved. Neither HTTP parameters nor
 * configuration values can supply arbitrary class names to this loader.
 */
spl_autoload_register(static function (string $className): void {
    $prefix = 'Sasd\\ApiServer\\';

    if (!str_starts_with($className, $prefix)) {
        return;
    }

    $relative = substr($className, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
