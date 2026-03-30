<?php

declare(strict_types=1);

// Disable deprecation notices starting from PHP 8.4
// Deprecated: funcAbc(): Implicitly marking parameter $xyz as nullable is deprecated, the explicit nullable type must be used instead
error_reporting(PHP_VERSION_ID < 80400 ? E_ALL : (E_ALL & ~E_DEPRECATED));

require __DIR__ . '/BootstrapToolsUtil.php';
