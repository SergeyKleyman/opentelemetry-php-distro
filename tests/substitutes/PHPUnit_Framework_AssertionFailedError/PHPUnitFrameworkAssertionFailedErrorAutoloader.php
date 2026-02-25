<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OTelDistroTests\substitutes;

use PHPUnit\Framework\AssertionFailedError;

final class PHPUnitFrameworkAssertionFailedErrorAutoloader
{
    private static bool $isClassLoaded = false;

    public static function register(): void
    {
        spl_autoload_register(
            static function (string $fqClassName): void {
                // Example of $fqClassName: PHPUnit\Framework\AssertionFailedError

                if (self::$isClassLoaded || $fqClassName !== AssertionFailedError::class) {
                    return;
                }

                require __DIR__ . '/patched/AssertionFailedError.php';

                self::$isClassLoaded = true;
            },
            prepend: true
        );
    }
}
