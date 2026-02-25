<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface ClockInterface
{
    /**
     * @return SystemTime UTC based and in microseconds since Unix epoch
     */
    public function getSystemClockCurrentTime(): SystemTime;

    /**
     * Clock that cannot be set and represents monotonic time since some unspecified starting point.
     * In microseconds.
     * Used to measure duration.
     *
     * @return MonotonicTime Monotonic time since some unspecified starting point in microseconds
     */
    public function getMonotonicClockCurrentTime(): MonotonicTime;
}
