<?php

declare(strict_types=1);

// Ensure that composer has installed all dependencies
if (!file_exists($vendorAutoload = (__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'))) {
    die("Error: $vendorAutoload is missing - dependencies must be installed using composer" . PHP_EOL);
}

// Disable deprecation notices starting from PHP 8.4
// Deprecated: funcAbc(): Implicitly marking parameter $xyz as nullable is deprecated, the explicit nullable type must be used instead
error_reporting(PHP_VERSION_ID < 80400 ? E_ALL : (E_ALL & ~E_DEPRECATED));

require $vendorAutoload;
require __DIR__ . '/otel_distro_extension_stubs/load.php';
