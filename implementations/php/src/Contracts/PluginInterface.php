<?php

declare(strict_types=1);

namespace Sasd\ApiServer\Contracts;

/**
 * Runtime contract for a trusted, in-process PHP extension.
 *
 * boot() is invoked only after the operator's private configuration is
 * validated AND the factory's identifier is explicitly known to the server.
 * A plugin must never register unguarded public routes or bypass the core's
 * eventual authorization/validation/database boundaries.
 */
interface PluginInterface
{
    public function id(): string;

    /** One of: logging, identity, storage, application. */
    public function category(): string;

    /** Initialize request-local dependencies; no dynamic downloading or eval. */
    public function boot(): void;
}
