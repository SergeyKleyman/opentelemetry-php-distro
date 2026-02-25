<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
trait ComparableTrait
{
    public function isEqual(self $other): bool
    {
        /** @noinspection PhpParamsInspection */
        return $this->compare($other) === 0;
    }

    public function isLessThan(self $other): bool
    {
        /** @noinspection PhpParamsInspection */
        return $this->compare($other) < 0;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        /** @noinspection PhpParamsInspection */
        return $this->compare($other) <= 0;
    }

    public function isGreaterThan(self $other): bool
    {
        /** @noinspection PhpParamsInspection */
        return $this->compare($other) > 0;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        /** @noinspection PhpParamsInspection */
        return $this->compare($other) >= 0;
    }
}
