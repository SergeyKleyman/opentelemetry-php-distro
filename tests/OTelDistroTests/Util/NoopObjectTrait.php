<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;
use OTelDistroTests\Util\Log\LogConsts;
use OTelDistroTests\Util\Log\LogStreamInterface;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
trait NoopObjectTrait
{
    use SingletonInstanceTrait;

    public function isNoop(): bool
    {
        return true;
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs([LogConsts::TYPE_KEY => ClassNameUtil::fqToShort(get_class($this))]);
    }
}
