<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class FloatLimits
{
    use StaticClassTrait;

    public const MAX = PHP_FLOAT_MAX;
    public const MIN = -self::MAX; // PHP_FLOAT_MIN is actually a positive min
}
