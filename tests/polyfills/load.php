<?php

declare(strict_types=1);

if (!class_exists('AllowDynamicProperties')) {
    require __DIR__ . '/AllowDynamicProperties.php';
}

// https://www.php.net/manual/en/class.override.php
// (PHP 8 >= 8.3.0)
if (!class_exists('Override')) {
    require __DIR__ . '/Override.php';
}
