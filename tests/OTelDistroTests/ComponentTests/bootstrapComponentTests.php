<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use OTelDistroTests\BootstrapTestsUtil;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ExceptionUtil;

require __DIR__ . '/../../bootstrap.php';

ExceptionUtil::runCatchLogRethrow(
    function (): void {
        BootstrapTestsUtil::bootstrapShared(dbgProcessName: 'Component tests');
        AmbientContextForTests::testConfig()->validateForComponentTests();
    }
);
