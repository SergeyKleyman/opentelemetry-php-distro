<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\StaticClassTrait;

final class LoggableToString
{
    use StaticClassTrait;

    public const DEFAULT_LENGTH_LIMIT = 1000;

    public static function convert(mixed $value, bool $prettyPrint = false, int $lengthLimit = self::DEFAULT_LENGTH_LIMIT): string
    {
        return LoggableToEncodedJson::convert($value, $prettyPrint, $lengthLimit);
    }
}
