<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Contracts;

/**
 * A compiled-in and reviewed registration point for extensions.
 *
 * No class names, factory paths, PHP code or package URLs are read from the
 * operator's JSON configuration: the configuration may only SELECT IDs of
 * factories already registered by trusted PHP bootstrap code.
 */
interface PluginFactoryInterface
{
    public function id(): string;

    public function category(): string;

    /**
     * Plugin contract version understood by this factory; NOT a product
     * version or a promise of public HTTP compatibility.
     */
    public function apiVersion(): string;

    public function create(): PluginInterface;
}
