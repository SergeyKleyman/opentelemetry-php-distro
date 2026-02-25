<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class FlagsUtil
{
    use StaticClassTrait;

    /**
     * @param array<int, string> $maskToName
     *
     * @return iterable<string>
     */
    public static function extractBitNames(int $flags, array $maskToName): iterable
    {
        foreach ($maskToName as $mask => $name) {
            if (($flags & $mask) !== 0) {
                yield $name;
            }
        }
    }
}
