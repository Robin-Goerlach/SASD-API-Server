<?php

declare(strict_types=1);

/**
 * Only public PHP entry point. The webserver document root is this directory.
 * Source, configuration and logs are physically outside it (not merely
 * protected by .htaccess, which may be ignored by shared hosting providers).
 */
require dirname(__DIR__) . '/autoload.php';

use Sasd\ApiServer\Bootstrap;
use Sasd\ApiServer\Http\Response;

$requestId = bin2hex(random_bytes(16));

try {
    // Production deployment MUST provide absolute private filesystem paths.
    // Never use query/header parameters to select configuration or plugins.
    $configPath = getenv('SASD_PLUGIN_CONFIG') ?: '';
    $logPath = getenv('SASD_TECHNICAL_LOG') ?: '';
    $application = Bootstrap::create($configPath, $logPath, __DIR__);
    $response = $application->handle($_SERVER, $_GET, $requestId);
} catch (Throwable) {
    // Fallback logs are generic: PHP's error-log destination must be checked
    // during hosting acceptance. No exception details are printed or echoed.
    error_log('SASD API Server: startup unavailable');
    $response = Response::failure(503, 'unavailable', 'Service unavailable.', $requestId);
}

$response->send($requestId);
