<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class Duration implements LoggableInterface
{
    public function __construct(
        public readonly float $value,
        public readonly DurationUnit $unit,
    ) {
    }

    public function compare(Duration $other): int
    {
        return NumericUtilForTests::compare($this->toMilliseconds(), $other->toMilliseconds());
    }

    public function equals(Duration $other): bool
    {
        return $this->toMilliseconds() === $other->toMilliseconds();
    }

    public function toMilliseconds(): float
    {
        return self::valueToMilliseconds($this->value, $this->unit);
    }

    public static function valueToMilliseconds(float $value, DurationUnit $unit): float
    {
        return $value * $unit->toMillisecondsFactor();
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs($this->value . $this->unit->name);
    }
}
