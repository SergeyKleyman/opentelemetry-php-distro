<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use OTelDistroTests\Util\MonotonicTime;
use OTelDistroTests\Util\SystemTime;

abstract class AgentBackendCommEvent implements LoggableInterface
{
    use LoggableTrait;

    public function __construct(
        public readonly MonotonicTime $monotonicTime,
        public readonly SystemTime $systemTime,
    ) {
    }
}
