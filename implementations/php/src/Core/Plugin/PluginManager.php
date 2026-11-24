<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Core\Plugin;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Sasd\ApiServer\Contracts\PluginFactoryInterface;
use Sasd\ApiServer\Contracts\PluginInterface;

/**
 * Manages only explicitly registered, trusted in-process plugins.
 *
 * This deliberately is not a plugin marketplace, file scanner, package
 * downloader or dynamic HTTP route loader. Configuration chooses from
 * reviewed factories, never from arbitrary classes or file paths.
 *
 * All registrations and enabled identifiers are validated before a plugin
 * boots. This limits accidental partial initialization, although a plugin's
 * own external side effects cannot be rolled back by this manager.
 */
final class PluginManager
{
    public const API_VERSION = '1.0';

    private const CATEGORIES = ['logging', 'identity', 'storage', 'application'];

    /** @var array<string, PluginFactoryInterface> */
    private array $factories = [];

    /** @var array<string, PluginInterface> */
    private array $active = [];

    private bool $started = false;

    public function register(PluginFactoryInterface $factory): void
    {
        if ($this->started) {
            throw new LogicException('Plugin registration is closed after startup.');
        }

        $id = $factory->id();
        self::validateId($id);
        self::validateCategory($factory->category());

        if ($factory->apiVersion() !== self::API_VERSION) {
            throw new InvalidArgumentException('Unsupported plugin contract version.');
        }

        if (isset($this->factories[$id])) {
            throw new InvalidArgumentException('Duplicate plugin identifier.');
        }

        $this->factories[$id] = $factory;
    }

    /**
     * @param list<string> $enabledIds Identifiers from *private* JSON config.
     */
    public function start(array $enabledIds): void
    {
        if ($this->started) {
            throw new LogicException('Plugin startup may only run once per kernel.');
        }

        $seen = [];
        $categories = [];

        // Phase 1: resolve the entire selection without instantiating code.
        foreach ($enabledIds as $id) {
            if (!is_string($id)) {
                throw new InvalidArgumentException('Plugin identifiers must be strings.');
            }

            self::validateId($id);

            if (isset($seen[$id]) || !isset($this->factories[$id])) {
                throw new InvalidArgumentException('Duplicate or unregistered plugin identifier.');
            }

            $seen[$id] = true;
            $category = $this->factories[$id]->category();

            // Exactly one provider per exclusive system capability. Multiple
            // application modules may be allowed in future reviewed releases.
            if ($category !== 'application' && isset($categories[$category])) {
                throw new InvalidArgumentException('Multiple providers for one exclusive plugin category.');
            }

            $categories[$category] = true;
        }

        // Independent of JSON ordering: diagnostic sink before identity,
        // identity before storage and future application modules.
        usort($enabledIds, function (string $a, string $b): int {
            $rankA = array_search($this->factories[$a]->category(), self::CATEGORIES, true);
            $rankB = array_search($this->factories[$b]->category(), self::CATEGORIES, true);
            return ($rankA <=> $rankB) ?: strcmp($a, $b);
        });

        // Phase 2: create and validate the full graph before booting anything.
        $prepared = [];
        foreach ($enabledIds as $id) {
            $factory = $this->factories[$id];
            $instance = $factory->create();

            if ($instance->id() !== $id || $instance->category() !== $factory->category()) {
                throw new RuntimeException('Plugin instance does not match its trusted factory.');
            }

            $prepared[$id] = $instance;
        }

        // Phase 3: boot only verified instances. Any exception propagates to
        // the startup boundary, which returns unavailable (never fail-open).
        foreach ($prepared as $instance) {
            $instance->boot();
        }

        $this->active = $prepared;
        $this->started = true;
    }

    /** @return list<string> */
    public function activeIds(): array
    {
        return array_keys($this->active);
    }

    private static function validateId(string $id): void
    {
        if (preg_match('/^[a-z][a-z0-9-]{1,63}$/D', $id) !== 1) {
            throw new InvalidArgumentException('Invalid plugin identifier.');
        }
    }

    private static function validateCategory(string $category): void
    {
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new InvalidArgumentException('Unknown plugin category.');
        }
    }
}
