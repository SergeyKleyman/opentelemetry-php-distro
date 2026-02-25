<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class OTelDistroExtensionUtil
{
    use StaticClassTrait;

    public const EXTENSION_NAME = 'opentelemetry_distro';

    private static ?bool $isLoaded = null;

    public static function isLoaded(): bool
    {
        if (self::$isLoaded === null) {
            self::$isLoaded = extension_loaded(self::EXTENSION_NAME);
        }

        return self::$isLoaded;
    }
}
