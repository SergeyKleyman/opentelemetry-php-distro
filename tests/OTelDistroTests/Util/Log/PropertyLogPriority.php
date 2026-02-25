<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class PropertyLogPriority
{
    use StaticClassTrait;

    public const MUST_BE_INCLUDED = 0;
    public const NORMAL = self::MUST_BE_INCLUDED + 1;
    public const LOW = self::NORMAL + 1;
}
