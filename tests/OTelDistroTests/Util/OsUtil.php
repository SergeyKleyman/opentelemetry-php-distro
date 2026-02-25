<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class OsUtil
{
    use StaticClassTrait;

    public static function isWindows(): bool
    {
        return strnatcasecmp(PHP_OS_FAMILY, 'Windows') === 0;
    }
}
