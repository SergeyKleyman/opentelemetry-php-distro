<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

/**
 * @phpstan-import-type Context from BootstrapStageLoggingClassTrait
 */
interface GetContextInterface
{
    /**
     * @return Context
     */
    public function getConext(): array;
}
