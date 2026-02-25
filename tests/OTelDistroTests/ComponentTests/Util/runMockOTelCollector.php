<?php

declare(strict_types=1);

require __DIR__ . '/../../../bootstrap.php';

use OTelDistroTests\ComponentTests\Util\MockOTelCollector;

MockOTelCollector::run();
