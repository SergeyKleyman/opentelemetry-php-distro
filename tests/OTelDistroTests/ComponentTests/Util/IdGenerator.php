<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class IdGenerator
{
    use StaticClassTrait;

    public static function generateId(int $idLengthInBytes): string
    {
        return self::convertBinaryIdToString(self::generateBinaryId($idLengthInBytes));
    }

    /**
     * @param array<int> $binaryId
     */
    public static function convertBinaryIdToString(array $binaryId): string
    {
        $result = '';
        foreach ($binaryId as $byte) {
            $result .= sprintf('%02x', $byte);
        }
        return $result;
    }

    /**
     * @return array<int>
     */
    private static function generateBinaryId(int $idLengthInBytes): array
    {
        $result = [];
        for ($i = 0; $i < $idLengthInBytes; ++$i) {
            $result[] = mt_rand(0, 255);
        }
        return $result;
    }
}
