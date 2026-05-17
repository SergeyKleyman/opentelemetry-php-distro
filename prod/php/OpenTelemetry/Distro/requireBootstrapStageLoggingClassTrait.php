<?php

declare(strict_types=1);

use OpenTelemetry\Distro\BootstrapStageLoggingClassTrait;
use OpenTelemetry\Distro\Util\GetContextInterface;

if (!trait_exists(BootstrapStageLoggingClassTrait::class)) {
    require __DIR__ . '/requireBootstrapStageLogger.php';

    if (!interface_exists(GetContextInterface::class)) {
        require __DIR__ . '/Util/GetContextInterface.php';
    }

    require __DIR__ . '/BootstrapStageLoggingClassTrait.php';
}
