<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
enum DurationUnit
{
    case ms;
    case s;
    case m;

    public function toMillisecondsFactor(): float
    {
        return match ($this) {
            self::ms => 1,
            self::s => 1000,
            self::m => 60 * 1000,
        };
    }
}
