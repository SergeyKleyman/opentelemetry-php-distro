<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class BoolUtilForTests
{
    use StaticClassTrait;

    public const INT_FOR_FALSE = 0;
    public const INT_FOR_TRUE = 1;
    public const ALL_VALUES = [true, false];
    public const ALL_NULLABLE_VALUES = [null, true, false];

    public static function ifThen(bool $ifCond, bool $thenCond): bool
    {
        return $ifCond ? $thenCond : true;
    }

    public static function toString(bool $val): string
    {
        return $val ? 'true' : 'false';
    }

    public static function toInt(bool $val): int
    {
        return $val ? self::INT_FOR_TRUE : self::INT_FOR_FALSE;
    }

    public static function fromString(string $stringVal): bool
    {
        /** @var list<string> $trueStringValues */
        static $trueStringValues = ['true', 'yes', 'on', '1'];

        foreach ($trueStringValues as $trueStringValue) {
            if (strcasecmp($stringVal, $trueStringValue) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<bool>
     */
    public static function allValuesStartingFrom(bool $startingValue): array
    {
        return [$startingValue, !$startingValue];
    }
}
