<?php

declare(strict_types=1);

use OpenTelemetry\Distro\Util\HiddenConstructorTrait;
use OpenTelemetry\Distro\Util\StaticClassTrait;

if (!trait_exists(StaticClassTrait::class)) {
    if (!trait_exists(HiddenConstructorTrait::class)) {
        require __DIR__ . '/Util/HiddenConstructorTrait.php';
    }

    require __DIR__ . '/Util/StaticClassTrait.php';
}
