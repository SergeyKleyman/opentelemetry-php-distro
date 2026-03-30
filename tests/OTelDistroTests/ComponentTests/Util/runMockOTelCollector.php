<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap_tests_shared.php';

use OTelDistroTests\ComponentTests\Util\MockOTelCollector;

MockOTelCollector::run();
