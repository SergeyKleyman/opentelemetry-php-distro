<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\Log\LoggableToString;
use PHPUnit\Framework\Assert;

final class CloneUtil
{
    use StaticClassTrait;

    public static function deepClone(mixed $val): mixed
    {
        if (($val === null) || is_scalar($val) || is_callable($val) || is_resource($val)) {
            return $val;
        }

        if (is_array($val)) {
            return array_map(fn($arrayElementValue) => CloneUtil::deepClone($arrayElementValue), $val);
        }

        if (is_object($val)) {
            return clone $val;
        }

        Assert::fail(LoggableToString::convertMessageAndContext('Unexpected $val type: ' . get_debug_type($val), compact('val')));
    }
}
