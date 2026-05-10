<?php

/**@noinspection PhpIncludeInspection */

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use OTelDistroTests\ComponentTests\DependenciesScopingTestApp\App;

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'Shared.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'App.php';
App::run();
