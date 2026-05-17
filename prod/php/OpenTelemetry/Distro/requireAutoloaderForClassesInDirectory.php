<?php

declare(strict_types=1);

use OpenTelemetry\Distro\AutoloaderForClassesInDirectory;
use OpenTelemetry\Distro\SplAutoloadFunctionsLogTrait;
use OpenTelemetry\Distro\SplAutoloadFunctionsLogUtil;

if (!class_exists(AutoloaderForClassesInDirectory::class)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '/requireBootstrapStageLogger.php';

    if (!class_exists(SplAutoloadFunctionsLogUtil::class)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'SplAutoloadFunctionsLogUtil.php';
    }

    if (!trait_exists(SplAutoloadFunctionsLogTrait::class)) {
        require __DIR__ . DIRECTORY_SEPARATOR . 'SplAutoloadFunctionsLogTrait.php';
    }

    require __DIR__ . DIRECTORY_SEPARATOR . 'AutoloaderForClassesInDirectory.php';
}
