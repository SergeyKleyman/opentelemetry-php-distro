<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\LogTests;

use OpenTelemetry\Distro\Log\LogLevel;
use OTelDistroTests\Util\Log\LogLevelUtil;
use OTelDistroTests\Util\TestCaseBase;

class LogLevelTest extends TestCaseBase
{
    public function testGetHighest(): void
    {
        self::assertSame(LogLevel::trace, LogLevelUtil::getHighest());
    }
}
