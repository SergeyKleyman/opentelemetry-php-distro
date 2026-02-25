<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\Util\DurationUnit;
use PHPUnit\Framework\TestCase;

class TimeDurationUnitsTest extends TestCase
{
    public function testSuffixAndIdIsInDescendingOrderOfSuffixLength(): void
    {
        /** @var ?int $prevSuffixLength */
        $prevSuffixLength = null;
        foreach (DurationUnit::cases() as $unit) {
            $suffixLength = strlen($unit->name);
            if ($prevSuffixLength !== null) {
                self::assertLessThanOrEqual($prevSuffixLength, $suffixLength);
            }
            $prevSuffixLength = $suffixLength;
        }
    }
}
