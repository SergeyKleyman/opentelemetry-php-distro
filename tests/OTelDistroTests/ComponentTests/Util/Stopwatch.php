<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\Clock;
use OTelDistroTests\Util\MonotonicTime;
use OTelDistroTests\Util\TimeUtil;

final class Stopwatch
{
    private readonly Clock $clock;
    private MonotonicTime $timeStarted;

    public function __construct()
    {
        $this->clock = AmbientContextForTests::clock();
        $this->restart();
    }

    public function elapsedInMicroseconds(): float
    {
        return TimeUtil::calcDurationInMicrosecondsClampNegativeToZero($this->timeStarted, $this->clock->getMonotonicClockCurrentTime());
    }

    public function restart(): void
    {
        $this->timeStarted = $this->clock->getMonotonicClockCurrentTime();
    }
}
