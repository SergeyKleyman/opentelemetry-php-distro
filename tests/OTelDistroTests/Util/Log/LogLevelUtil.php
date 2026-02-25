<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\IterableUtil;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class LogLevelUtil
{
    use StaticClassTrait;

    public static function getHighest(): LogLevel
    {
        /** @var ?LogLevel $result */
        static $result = null;

        if ($result === null) {
            $maxValue = IterableUtil::max(IterableUtil::map(LogLevel::cases(), fn($logLevel): int => $logLevel->value));
            /** @var int $maxValue */
            $result = LogLevel::from($maxValue);
        }

        return $result;
    }
}
