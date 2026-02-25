<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OTelDistroTests\substitutes;

use RuntimeException;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class SubstitutesUtil
{
    private static function appendStackTraceToMessage(string $msg): string
    {
        return $msg . '; stack trace: ' . json_encode(debug_backtrace(), flags: JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @param class-string<object> $className
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function assertClassNotLoaded(string $className, bool $autoload): void
    {
        if (class_exists($className, $autoload)) {
            throw new RuntimeException(self::appendStackTraceToMessage('Class ' . $className . ' IS loaded'));
        }
    }

    /**
     * @param class-string<object> $className
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function assertClassLoaded(string $className, bool $autoload): void
    {
        if (!class_exists($className, $autoload)) {
            throw new RuntimeException(self::appendStackTraceToMessage('Class ' . $className . ' is NOT loaded'));
        }
    }

    /**
     * @param class-string<object> $className
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function assertClassHasProperty(string $className, string $propertyName): void
    {
        if (!property_exists($className, $propertyName)) {
            throw new RuntimeException(self::appendStackTraceToMessage('Class ' . $className . ' does have property ' . $propertyName));
        }
    }
}
