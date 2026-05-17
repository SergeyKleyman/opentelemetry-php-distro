<?php

declare(strict_types=1);

use OpenTelemetry\Distro\AutoloaderForClassesInDirectory;

if (!class_exists(AutoloaderForClassesInDirectory::class)) {
    require __DIR__ . DIRECTORY_SEPARATOR . '/requireBootstrapStageLoggingClassTrait.php';

    require __DIR__ . DIRECTORY_SEPARATOR . 'AutoloaderForClassesInDirectory.php';
}
