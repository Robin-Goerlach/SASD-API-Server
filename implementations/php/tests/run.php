<?php

declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

use Sasd\ApiServer\Bootstrap;
use Sasd\ApiServer\Config\PluginConfigLoader;
use Sasd\ApiServer\Contracts\PluginFactoryInterface;
use Sasd\ApiServer\Contracts\PluginInterface;
use Sasd\ApiServer\Core\Plugin\PluginManager;
use Sasd\ApiServer\Http\Request;
use Sasd\ApiServer\Infrastructure\Logging\JsonLineTechnicalLogger;

/**
 * Executable dependency-free unit/smoke tests. No internet or database used.
 * All temporary data is placed outside the webroot and deleted afterwards.
 */
$assertions = 0;
function check(bool $condition, string $message): void
{
    global $assertions;
    ++$assertions;
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $message);
    }
}
function expectFailure(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (Throwable) {
        check(true, $message);
        return;
    }
    check(false, $message);
}

$workspace = sys_get_temp_dir() . '/sasd-api-tests-' . bin2hex(random_bytes(6));
$private = $workspace . '/private';
$webroot = dirname(__DIR__) . '/webroot';
mkdir($private, 0700, true);
$config = $private . '/plugins.json';
$log = $private . '/technical.jsonl';
file_put_contents($config, '{"schemaVersion":1,"enabled":[]}');

try {
    $rid = '0123456789abcdef0123456789abcdef';
    $kernel = Bootstrap::create($config, $log, $webroot);
    check($kernel->activePluginIds() === [], 'default plugin list empty');

    $health = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v0/health'], [], $rid);
    check($health->status === 200 && ($health->body['data']['status'] ?? '') === 'ok', 'only health route exists');

    $fallback = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/index.php?route=/api/v0/health'], ['route' => '/api/v0/health'], $rid);
    check($fallback->status === 200 && $fallback->body === $health->body, 'query routing and clean routing agree');

    $unknown = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v0/books'], [], $rid);
    check($unknown->status === 404 && !isset($unknown->body['data']), 'no implicit data routes');

    $method = $kernel->handle(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v0/health'], [], $rid);
    check($method->status === 405 && $method->headers['Allow'] === 'GET', 'health method is restricted');

    $invalid = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/index.php'], ['route' => ['bad']], $rid);
    check($invalid->status === 400, 'array query route rejected');

    $traversal = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/index.php'], ['route' => '/../private'], $rid);
    check($traversal->status === 400, 'path traversal rejected');

    $ambiguous = $kernel->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v0/health?route=/api/v0/books'], ['route' => '/api/v0/books'], $rid);
    check($ambiguous->status === 400, 'ambiguous routing rejected');

    $lines = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    check(count($lines) === 7, 'each handled request has exactly one technical log');
    foreach ($lines as $line) {
        $event = json_decode($line, true, 32, JSON_THROW_ON_ERROR);
        check(array_keys($event) === ['timestamp', 'request_id', 'method', 'status', 'event', 'duration_ms'], 'no untrusted fields in sanitized logs');
        check($event['request_id'] === $rid && !str_contains($line, '/private'), 'log has safe request ID and no private path');
    }

    $loader = new PluginConfigLoader();
    expectFailure(fn() => $loader->load('', $webroot), 'missing private config fails closed');
    file_put_contents($config, '{broken');
    expectFailure(fn() => $loader->load($config, $webroot), 'invalid JSON rejected');
    file_put_contents($config, '{"enabled":[],"schemaVersion":1}');
    check($loader->load($config, $webroot) === [], 'JSON keys may be reordered safely');
    file_put_contents($config, '{"schemaVersion":2,"enabled":[]}');
    expectFailure(fn() => $loader->load($config, $webroot), 'unsupported schema version rejected');
    file_put_contents($config, '{"schemaVersion":1,"enabled":[],"debugPassword":"not-a-secret"}');
    expectFailure(fn() => $loader->load($config, $webroot), 'unknown JSON fields rejected');
    file_put_contents($config, '{"schemaVersion":1,"enabled":["nonexistent"]}');
    expectFailure(fn() => Bootstrap::create($config, $log, $webroot), 'unregistered plugin denied');
    file_put_contents($config, '{"schemaVersion":1,"enabled":{},"other":1}');
    expectFailure(fn() => $loader->load($config, $webroot), 'malformed schema fails closed');
    file_put_contents($config, '{"schemaVersion":1,"enabled":[]}');

    $publicConfig = $webroot . '/_temporary_sasd_plugin_test.json';
    try {
        file_put_contents($publicConfig, '{"schemaVersion":1,"enabled":[]}');
        expectFailure(fn() => $loader->load($publicConfig, $webroot), 'configuration inside webroot rejected');
    } finally {
        @unlink($publicConfig);
    }
    expectFailure(fn() => new JsonLineTechnicalLogger($webroot . '/log.jsonl', $webroot), 'log inside webroot rejected');
    $failedLogKernel = Bootstrap::create($config, '/proc/sasd-api-nonexistent-test.jsonl', $webroot);
    $failedLogResponse = $failedLogKernel->handle(['REQUEST_METHOD'=>'GET', 'REQUEST_URI'=>'/api/v0/health'], [], $rid);
    check($failedLogResponse->status === 503 && ($failedLogResponse->body['error']['code'] ?? '') === 'unavailable', 'log write error forces 503');

    /** A trusted factory is installed by code, not discovered by filename. */
    $factory = new class implements PluginFactoryInterface {
        public function id(): string { return 'test-logger'; }
        public function category(): string { return 'logging'; }
        public function apiVersion(): string { return '1.0'; }
        public function create(): PluginInterface {
            return new class implements PluginInterface {
                public function id(): string { return 'test-logger'; }
                public function category(): string { return 'logging'; }
                public function boot(): void { /* Test has no external side effects. */ }
            };
        }
    };
    $manager = new PluginManager();
    $manager->register($factory);
    $manager->start(['test-logger']);
    check($manager->activeIds() === ['test-logger'], 'explicitly trusted plugin boots');
    expectFailure(fn() => $manager->register($factory), 'no runtime registration');
    expectFailure(fn() => $manager->start([]), 'no repeated plugin boot');
    $duplicate = new PluginManager();
    $duplicate->register($factory);
    expectFailure(fn() => $duplicate->register($factory), 'duplicate factory registration rejected');
    expectFailure(fn() => $duplicate->start(['test-logger', 'test-logger']), 'duplicate selected IDs rejected');
    $wrongVersion = new class implements PluginFactoryInterface {
        public function id(): string { return 'wrong-version'; }
        public function category(): string { return 'logging'; }
        public function apiVersion(): string { return '2.0'; }
        public function create(): PluginInterface { throw new RuntimeException('never invoked'); }
    };
    expectFailure(fn() => (new PluginManager())->register($wrongVersion), 'incompatible plugin API rejected');
    $wrongInstance = new class implements PluginFactoryInterface {
        public function id(): string { return 'wrong-instance'; }
        public function category(): string { return 'identity'; }
        public function apiVersion(): string { return '1.0'; }
        public function create(): PluginInterface {
            return new class implements PluginInterface {
                public function id(): string { return 'other-id'; }
                public function category(): string { return 'identity'; }
                public function boot(): void { }
            };
        }
    };
    $mismatch = new PluginManager();
    $mismatch->register($wrongInstance);
    expectFailure(fn() => $mismatch->start(['wrong-instance']), 'factory/instance mismatch fails closed');
    expectFailure(fn() => (new PluginManager())->start(['test-logger']), 'unregistered names fail closed');

    file_put_contents($config, '{"schemaVersion":1,"enabled":["test-logger"]}');
    $pluginKernel = Bootstrap::create($config, $log, $webroot, [$factory]);
    check($pluginKernel->activePluginIds() === ['test-logger'], 'bootstrap composes trusted factory');
    check($pluginKernel->handle(['REQUEST_METHOD'=>'GET', 'REQUEST_URI'=>'/api/v0/health'], [], $rid)->status === 200, 'trusted plugin keeps health functioning');

    echo "PASS: {$assertions} assertions in PHP " . PHP_VERSION . "\n";
} finally {
    @unlink($config);
    @unlink($log);
    @rmdir($private);
    @rmdir($workspace);
}
