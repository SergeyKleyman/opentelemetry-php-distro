<?php

/** @noinspection PhpUnusedPrivateFieldInspection */

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\LogTests;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use RuntimeException;

class ObjectThrowingInToLog implements LoggableInterface
{
    public function toLog(LogStreamInterface $stream): void
    {
        throw new RuntimeException('Dummy thrown on purpose');
    }
}
