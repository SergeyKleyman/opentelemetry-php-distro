<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OpenTelemetry\Distro\Util\BoolUtil;
use OTelDistroTests\Util\BoolUtilForTests;
use PHPUnit\Framework\TestCase;

class BoolUtilTest extends TestCase
{
    public function testIfThen(): void
    {
        self::assertTrue(BoolUtilForTests::ifThen(true, true));
        self::assertTrue(BoolUtilForTests::ifThen(false, true));
        self::assertTrue(BoolUtilForTests::ifThen(false, false));

        self::assertTrue(!BoolUtilForTests::ifThen(true, false));
    }

    public function testToInt(): void
    {
        self::assertSame(1, BoolUtilForTests::toInt(true));
        self::assertSame(0, BoolUtilForTests::toInt(false));
    }

    public function testToString(): void
    {
        self::assertSame('true', BoolUtil::toString(true));
        self::assertSame('false', BoolUtil::toString(false));
    }

    public function testFromString(): void
    {
        foreach ([true, false] as $boolVal) {
            self::assertSame($boolVal, BoolUtilForTests::fromString(BoolUtil::toString($boolVal)));
        }
    }
}
