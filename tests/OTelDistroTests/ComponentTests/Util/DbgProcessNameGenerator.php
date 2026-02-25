<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

final class DbgProcessNameGenerator
{
    use StaticClassTrait;

    /** @var array<string, positive-int> */
    private static array $prefixToNextIndex = [];

    public static function generate(string $prefix): string
    {
        if (!array_key_exists($prefix, self::$prefixToNextIndex)) {
            self::$prefixToNextIndex[$prefix] = 1;
        }
        $index = self::$prefixToNextIndex[$prefix]++;

        return $prefix . '_' . $index;
    }
}
