<?php

declare(strict_types=1);

use OpenTelemetry\Distro\BootstrapStageLogger;
use OpenTelemetry\Distro\Log\LogFeature;

if (!class_exists(BootstrapStageLogger::class)) {
    require __DIR__ . '/requireStaticClassTrait.php';

    if (!trait_exists(LogFeature::class)) {
        require __DIR__ . '/Log/LogFeature.php';
    }

    require __DIR__ . '/BootstrapStageLogger.php';
}
