<?php

declare(strict_types=1);

use OpenTelemetry\Distro\BootstrapStageLogger;
use OpenTelemetry\Distro\Util\HiddenConstructorTrait;
use OpenTelemetry\Distro\Util\StaticClassTrait;

if (!class_exists(BootstrapStageLogger::class)) {
    if (!trait_exists(HiddenConstructorTrait::class)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Util/HiddenConstructorTrait.php';
    }

    if (!trait_exists(StaticClassTrait::class)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Util/StaticClassTrait.php';
    }

    require __DIR__ . DIRECTORY_SEPARATOR . 'BootstrapStageLogger.php';
}
