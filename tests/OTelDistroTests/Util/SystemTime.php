<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class SystemTime
{
    public function __construct(
        public readonly float $value
    ) {
    }
}
