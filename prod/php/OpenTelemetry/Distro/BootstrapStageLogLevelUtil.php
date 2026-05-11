<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

final class BootstrapStageLogLevelUtil
{
    public const LEVEL_OFF = 0;
    public const LEVEL_CRITICAL = 1;
    public const LEVEL_ERROR = 2;
    public const LEVEL_WARNING = 3;
    public const LEVEL_INFO = 4;
    public const LEVEL_DEBUG = 5;
    public const LEVEL_TRACE = 6;

    private const LEVEL_AS_STRING = [
        self::LEVEL_OFF => 'OFF',
        self::LEVEL_CRITICAL => 'CRITICAL',
        self::LEVEL_ERROR => 'ERROR',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_INFO => 'INFO',
        self::LEVEL_DEBUG => 'DEBUG',
        self::LEVEL_TRACE => 'TRACE',
    ];

    public static function levelIntToString(int $level): string
    {
        if (array_key_exists($level, self::LEVEL_AS_STRING)) {
            return self::LEVEL_AS_STRING[$level];
        }

        return "LEVEL $level";
    }

    public static function levelStringToInt(string $levelString): ?int
    {
        /** @var ?array<string, int> $levelStringToInt */
        static $levelStringToInt = null;
        if ($levelStringToInt === null) {
            $levelStringToInt = [];
            foreach (self::LEVEL_AS_STRING as $currLevelInt => $currLevelString) {
                $levelStringToInt[strtoupper($currLevelString)] = $currLevelInt;
            }
        }

        $levelStringUpper = strtoupper($levelString);
        return array_key_exists($levelStringUpper, $levelStringToInt) ? $levelStringToInt[$levelStringUpper] : null;
    }
}
