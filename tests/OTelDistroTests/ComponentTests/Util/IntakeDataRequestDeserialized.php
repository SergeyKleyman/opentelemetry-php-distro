<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;

abstract class IntakeDataRequestDeserialized implements LoggableInterface
{
    use LoggableTrait;

    protected function __construct(
        public readonly IntakeDataRequestRaw $raw,
    ) {
    }

    abstract public function isEmptyAfterDeserialization(): bool;
}
