<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OpenTelemetry\Distro\Util\NumericUtil;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\FloatLimits;
use OTelDistroTests\Util\NumericUtilForTests;
use OTelDistroTests\Util\TestCaseBase;
use InvalidArgumentException;

class NumericUtilTest extends TestCaseBase
{
    public function testIsInClosedInterval(): void
    {
        // int - inside
        self::assertTrue(NumericUtil::isInClosedInterval(0, 1, 1));
        self::assertTrue(NumericUtil::isInClosedInterval(0, 0, 10));
        self::assertTrue(NumericUtil::isInClosedInterval(0, 1, 10));
        self::assertTrue(NumericUtil::isInClosedInterval(0, 5, 10));
        self::assertTrue(NumericUtil::isInClosedInterval(0, 10, 10));
        self::assertTrue(NumericUtil::isInClosedInterval(0, 0, 1));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, 0, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, 1, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, -1, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, 123, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, -123, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, PHP_INT_MAX - 1, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, PHP_INT_MIN + 2, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, PHP_INT_MAX, PHP_INT_MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(PHP_INT_MIN, PHP_INT_MIN, PHP_INT_MAX));

        // int - outside
        self::assertTrue(!NumericUtil::isInClosedInterval(0, 2, 1));
        self::assertTrue(!NumericUtil::isInClosedInterval(0, -1, 1));
        self::assertTrue(!NumericUtil::isInClosedInterval(0, -1, 10));
        self::assertTrue(!NumericUtil::isInClosedInterval(-10, -15, 10));

        // float - inside
        self::assertTrue(NumericUtil::isInClosedInterval(-20.5, 0, 10.5));
        self::assertTrue(NumericUtil::isInClosedInterval(-20.5, -20.5, 10.5));
        self::assertTrue(NumericUtil::isInClosedInterval(-20.5, 10.5, 10.5));
        self::assertTrue(NumericUtil::isInClosedInterval(-1.2, 3.4, 3.4));
        self::assertTrue(NumericUtil::isInClosedInterval(-1.2, 3.3, 3.4));
        self::assertTrue(NumericUtil::isInClosedInterval(-1.2, -1.2, 3.4));
        self::assertTrue(NumericUtil::isInClosedInterval(-1.2, -1.1, 3.4));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, 0, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, 1.2, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, -1.2, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, 123.4, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, -123.4, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, FloatLimits::MAX - 0.1, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, FloatLimits::MIN + 0.1, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, FloatLimits::MAX, FloatLimits::MAX));
        self::assertTrue(NumericUtil::isInClosedInterval(FloatLimits::MIN, FloatLimits::MIN, FloatLimits::MAX));

        // float - outside
        self::assertTrue(!NumericUtil::isInClosedInterval(-1.2, -1.201, 3.4));
        self::assertTrue(!NumericUtil::isInClosedInterval(-1.2, 3.401, 3.4));
        self::assertTrue(!NumericUtil::isInClosedInterval(-20.5, -20.501, 10.5));
        self::assertTrue(!NumericUtil::isInClosedInterval(-20.5, 10.501, 10.5));
    }

    public function testCompare(): void
    {
        $impl = function (int|float $lhs, string $expectedRelation, int|float $rhs): void {
            switch ($expectedRelation) {
                case '<':
                    self::assertLessThan(0, NumericUtilForTests::compare($lhs, $rhs));
                    break;
                case '=':
                    self::assertSame(0, NumericUtilForTests::compare($lhs, $rhs));
                    break;
                case '>':
                    self::assertGreaterThan(0, NumericUtilForTests::compare($lhs, $rhs));
                    break;
                default:
                    self::fail('Unknown relation `' . $expectedRelation . '\'');
            }
        };

        $impl(0, '=', 0);
        $impl(1, '=', 1);
        $impl(2.3, '=', 2.3);

        $impl(4.5, '<', 5);
        $impl(6, '<', 7.8);
        $impl(9.1, '<', 10.2);

        $impl(5, '>', 4.3);
        $impl(7.3, '>', 6);
        $impl(9.2, '>', 9.1);
    }

    public function testCompareSequencesValidInput(): void
    {
        /**
         * @template TNumber of int|float
         *
         * @phpstan-param array<TNumber> $lhs
         * @phpstan-param array<TNumber> $rhs
         */
        $impl = function (array $lhs, array $rhs, int $expectedRetVal): void {
            /** @var array<int|float> $lhs */
            /** @var array<int|float> $rhs */
            $actualRetVal = NumericUtilForTests::compareSequences($lhs, $rhs);
            self::assertSame($expectedRetVal, $actualRetVal);
        };

        $impl([], [], 0);
        $impl([1], [1], 0);
        $impl([1, 2], [1, 2], 0);
        $impl([1.1, 2.2, 3.3], [1.1, 2.2, 3.3], 0);

        $impl([1], [2], -1);
        $impl([2], [1], 1);
        $impl([1, 1], [1, 2], -1);
        $impl([1, 2], [1, 1], 1);
    }

    public function testCompareSequencesInvalidInput(): void
    {
        /**
         * @template TNumber of int|float
         *
         * @param array<TNumber> $lhs
         * @param array<TNumber> $rhs
         */
        $impl = function (array $lhs, array $rhs): void {
            /** @var array<int|float> $lhs */
            /** @var array<int|float> $rhs */
            AssertEx::throws(InvalidArgumentException::class, fn() => NumericUtilForTests::compareSequences($lhs, $rhs));
        };

        $impl([1], []);
    }
}
