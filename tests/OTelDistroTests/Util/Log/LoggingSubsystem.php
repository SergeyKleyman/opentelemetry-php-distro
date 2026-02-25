<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\ExceptionUtil;
use Throwable;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class LoggingSubsystem
{
    use StaticClassTrait;

    /** @var bool */
    public static $isInTestingContext = false;

    /** @var bool */
    private static $wereThereAnyInternalFailures = false;

    /**
     * @param string               $message
     * @param array<string, mixed> $context
     * @param Throwable            $causedBy
     *
     * @return string
     */
    public static function onInternalFailure(string $message, array $context, Throwable $causedBy): string
    {
        self::$wereThereAnyInternalFailures = true;
        if (self::$isInTestingContext) {
            throw new LoggingSubsystemException(ExceptionUtil::buildMessage($message, $context), $causedBy);
        }

        return $message . '. ' . LoggableToString::convert($context + ['causedBy' => $causedBy]);
    }

    public static function wereThereAnyInternalFailures(): bool
    {
        return self::$wereThereAnyInternalFailures;
    }
}
