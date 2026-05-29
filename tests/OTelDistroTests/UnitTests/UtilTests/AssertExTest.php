<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use Ds\Map as DsMap;
use Ds\PriorityQueue as DsPriorityQueue;
use Ds\Set as DsSet;
use OTelDistroTests\UnitTests\Util\CloneUtil;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\DeepEqual;
use OTelDistroTests\Util\DummyExceptionForTests;
use OTelDistroTests\Util\Log\StdError;
use OTelDistroTests\Util\Log\StdOut;
use OTelDistroTests\Util\TestCaseBase;
use PHPUnit\Exception as PHPUnitExceptionInterface;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * @phpstan-type NoParamsReturnVoidCallable callable(): void
 * @phpstan-type ThrowableDerivedClassString class-string<Throwable>
 * @phpstan-type AssertThrowsImpl callable(ThrowableDerivedClassString, ?string, ?int, NoParamsReturnVoidCallable): void
 */
final class AssertExTest extends TestCaseBase
{
    /**
     * @phpstan-param ThrowableDerivedClassString $expectedThrowableClass
     * @phpstan-param NoParamsReturnVoidCallable $actualCodeThatShouldThrow
     */
    private function assertThrowsPhpUnitBuiltInImpl(string $expectedThrowableClass, ?string $expectedThrowableMessage, ?int $expectedThrowableCode, callable $actualCodeThatShouldThrow): void
    {
        $this->expectException($expectedThrowableClass);

        if ($expectedThrowableMessage !== null) {
            $this->expectExceptionMessage($expectedThrowableMessage);
        }
        if ($expectedThrowableCode !== null) {
            $this->expectExceptionCode($expectedThrowableCode);
        }

        $actualCodeThatShouldThrow();
    }

    /**
     * @phpstan-param AssertThrowsImpl $assertThrowsImpl
     */
    private static function verifyAssertThrowsImpl(callable $assertThrowsImpl, string $exceptionMessage, int $exceptionCode): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        foreach ([true, false] as $actuallyThrows) {
            foreach ([$exceptionMessage, $exceptionMessage . ' suffix to cause mismatch'] as $actualMessage) {
                foreach ([$exceptionCode, $exceptionCode + 1] as $actualCode) {
                    $shouldExpectFailure = (!$actuallyThrows) || ($exceptionMessage !== $actualMessage) || ($exceptionCode !== $actualCode);
                    try {
                        $assertThrowsImpl(
                            DummyExceptionForTests::class,
                            $exceptionMessage,
                            $exceptionCode,
                            static function () use ($actuallyThrows, $actualMessage, $actualCode): void {
                                if ($actuallyThrows) {
                                    throw new DummyExceptionForTests($actualMessage, $actualCode);
                                }
                            },
                        );
                        self::assertFalse($shouldExpectFailure);
                    } catch (PHPUnitExceptionInterface $ex) {
                        $dbgCtx->add(compact('ex'));
                        self::assertTrue($shouldExpectFailure);
                    }
                }
            }
        }
    }

    public function testThrows(): void
    {
        /** @phpstan-var string $exceptionMessage */
        static $exceptionMessage = 'Dummy message to test assert-throws implementations';
        /** @phpstan-var int $exceptionCode */
        static $exceptionCode = 321;

        self::verifyAssertThrowsImpl(AssertEx::throwsWithMessageCode(...), $exceptionMessage, $exceptionCode);

        // Run the same verification of PHPUnit's built-in way to assert that some code throws
        self::verifyAssertThrowsImpl(self::assertThrowsPhpUnitBuiltInImpl(...), $exceptionMessage, $exceptionCode);

        /**
         * @phpstan-param ThrowableDerivedClassString $expectedThrowableClass
         * @phpstan-param NoParamsReturnVoidCallable $actualCodeThatShouldThrow
         */
        $incorrectAssertThrowsImpl = function (string $expectedThrowableClass, ?string $expectedThrowableMessage, ?int $expectedThrowableCode, callable $actualCodeThatShouldThrow): void {
            $actualCodeThatShouldThrow();
        };
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => self::verifyAssertThrowsImpl($incorrectAssertThrowsImpl, $exceptionMessage, $exceptionCode));

        /**
         * @phpstan-param ThrowableDerivedClassString $expectedThrowableClass
         * @phpstan-param NoParamsReturnVoidCallable $actualCodeThatShouldThrow
         */
        $incorrectAssertThrowsImpl = function (string $expectedThrowableClass, ?string $expectedThrowableMessage, ?int $expectedThrowableCode, callable $actualCodeThatShouldThrow): void {
            try {
                $actualCodeThatShouldThrow();
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch (DummyExceptionForTests $_) {
            }
        };
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => self::verifyAssertThrowsImpl($incorrectAssertThrowsImpl, $exceptionMessage, $exceptionCode));
    }

    public static function testNotEmptyString(): void
    {
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => AssertEx::notEmptyString(''));

        AssertEx::notEmptyString('a');
        AssertEx::notEmptyString('abc');
        AssertEx::notEmptyString(' ');
        AssertEx::notEmptyString('0');
        AssertEx::notEmptyString('0.0');
        AssertEx::notEmptyString('1');

        // Compare to PHPUnit's Assert::assertNotEmpty

        // Corrent cases:
        Assert::assertEmpty(''); // @phpstan-ignore staticMethod.alreadyNarrowedType
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => Assert::assertNotEmpty('')); // @phpstan-ignore staticMethod.impossibleType
        Assert::assertNotEmpty('abc'); // @phpstan-ignore staticMethod.alreadyNarrowedType
        Assert::assertNotEmpty(' '); // @phpstan-ignore staticMethod.alreadyNarrowedType
        Assert::assertNotEmpty('0.0'); // @phpstan-ignore staticMethod.alreadyNarrowedType
        Assert::assertNotEmpty('1'); // @phpstan-ignore staticMethod.alreadyNarrowedType

        // Incorrent cases:
        Assert::assertEmpty('0'); // @phpstan-ignore staticMethod.alreadyNarrowedType
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => Assert::assertNotEmpty('0')); // @phpstan-ignore staticMethod.impossibleType

        // Cases on non-string values

        Assert::assertEmpty(null); // @phpstan-ignore staticMethod.alreadyNarrowedType
        /** @noinspection PhpUnitAssertCanBeReplacedWithEmptyInspection */
        Assert::assertTrue(empty($undefinedVariable)); // @phpstan-ignore staticMethod.alreadyNarrowedType, empty.variable
    }

    public static function testEqual(): void
    {
        $assertEqualsInBothDirections = static function (mixed $a, mixed $b): void {
            AssertEx::equal($a, $b);
            AssertEx::equal($b, $a);
        };

        $assertNotEquals = static function (mixed $a, mixed $b): void {
            AssertEx::throws(PHPUnitExceptionInterface::class, fn() => AssertEx::equal($a, $b));
        };

        $assertNotEqualsInBothDirections = static function (mixed $a, mixed $b) use ($assertNotEquals): void {
            $assertNotEquals($a, $b);
            $assertNotEquals($b, $a);
        };

        $values = [null, true, false, 0, 1, 2, 3, -1.5, 2.6, '', 'abc', [], ['a', 2, 3.4], ['k1' => 'v1', 'k2' => 'v2']];
        $dummyObj1 = (new DummyDto())->setListArray(['a', 2, 3.4])->setMapArray(['k1' => 'v1', 'k2' => 'v2']);
        $values[] = $dummyObj1;
        $dummyObj2 = (new DummyDto())->setInt(123)->setString('def')->setNullableObject($dummyObj1);
        $values[] = $dummyObj2;

        foreach ($values as $val1) {
            $assertEqualsInBothDirections($val1, CloneUtil::deepClone($val1));
            foreach ($values as $val2) {
                if ($val1 !== $val2) {
                    $assertNotEquals($val1, $val2);
                }
            }
        }

        // Test objects implementing ArrayAccess and is iterable
        $assertEqualsInBothDirections(new DsMap(), new DsMap([]));
        $assertEqualsInBothDirections(new DsMap(['key_1' => 'val_1', 'key_2' => 'val_2']), new DsMap(['key_1' => 'val_1', 'key_2' => 'val_2']));
        $assertEqualsInBothDirections(new DsMap(['key_1' => 'val_1', 'key_2' => 'val_2']), new DsMap(['key_2' => 'val_2', 'key_1' => 'val_1']));
        $assertEqualsInBothDirections(
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']),
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']),
        );
        $assertEqualsInBothDirections(
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']),
            self::createDsMapFromPairs([$dummyObj2, 'dummyObj2'], [$dummyObj1, 'dummyObj1']),
        );

        // One map is proper submap of the other
        $assertNotEqualsInBothDirections(new DsMap(['dummyObj1' => $dummyObj1]), new DsMap());
        $assertNotEqualsInBothDirections(self::createDsMapFromPairs([$dummyObj1, 'dummyObj1']), new DsMap());
        $assertNotEqualsInBothDirections(self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']), self::createDsMapFromPairs([$dummyObj1, 'dummyObj1']));

        // Keys mapped to wrong values
        $assertNotEqualsInBothDirections(
            new DsMap(['dummyObj1' => $dummyObj1, 'dummyObj2' => $dummyObj2]),
            new DsMap(['dummyObj2' => $dummyObj1, 'dummyObj1' => $dummyObj2])
        );
        $assertNotEqualsInBothDirections(
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']),
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj1'])
        );
        $assertNotEqualsInBothDirections(
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj1'], [$dummyObj2, 'dummyObj2']),
            self::createDsMapFromPairs([$dummyObj1, 'dummyObj2'], [$dummyObj2, 'dummyObj2'])
        );

        $assertEqualsInBothDirections(new DsSet(['val_1', 'val_2']), new DsSet(['val_1', 'val_2']));
        $assertEqualsInBothDirections(new DsSet(['val_1', 'val_2']), new DsSet(['val_2', 'val_1']));
        $assertNotEqualsInBothDirections(new DsSet(['val_1', 'val_2a']), new DsSet(['val_1', 'val_2b']));
        $assertNotEqualsInBothDirections(new DsSet(['val_1']), new DsSet(['val_1', 'val_2']));
        $assertNotEqualsInBothDirections(new DsSet(['val_1', 'val_2']), new DsSet(['val_1', 'val_2', 'val_3']));

        // Test objects that are iterable only

        $assertEqualsInBothDirections(self::createDsPriorityQueue('val_1', 'val_2'), self::createDsPriorityQueue('val_1', 'val_2'));
        $assertEqualsInBothDirections(self::createDsPriorityQueue($dummyObj1, $dummyObj2), self::createDsPriorityQueue($dummyObj1, $dummyObj2));
        $assertNotEqualsInBothDirections(self::createDsPriorityQueue('val_1', 'val_2a'), self::createDsPriorityQueue('val_1', 'val_2b'));
        $assertNotEqualsInBothDirections(self::createDsPriorityQueue('val_1', 'val_2'), self::createDsPriorityQueue('val_2', 'val_1'));
        $assertNotEqualsInBothDirections(self::createDsPriorityQueue($dummyObj1, $dummyObj2), self::createDsPriorityQueue($dummyObj2, $dummyObj1));

        // Test objects that have equals() method

        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(123), self::createObjectWithMixedEqualsMethod(123));
        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod('abc'), self::createObjectWithMixedEqualsMethod('abc'));
        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod('abc'), 'abc');
        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(StdOut::singletonInstance()->getStream()), StdOut::singletonInstance()->getStream());
        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod('abc')), 'abc');
        $assertEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod(null)), null);

        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(123), self::createObjectWithMixedEqualsMethod(321));
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod('abc'), self::createObjectWithMixedEqualsMethod('xyz'));
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod('abc'), 'xyz');
        self::assertNotNull(StdOut::singletonInstance()->getStream());
        self::assertNotNull(StdError::singletonInstance()->getStream());
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(StdOut::singletonInstance()->getStream()), StdError::singletonInstance()->getStream());
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod('abc')), null);
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod(null)), 123);
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod(123)), null);
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod(null)), 'abc');
        $assertNotEqualsInBothDirections(self::createObjectWithMixedEqualsMethod(self::createObjectWithMixedEqualsMethod('abc')), null);

        $assertEqualsInBothDirections(self::createObjectWithObjectEqualsMethod(123), self::createObjectWithObjectEqualsMethod(123));
        $assertNotEqualsInBothDirections(self::createObjectWithObjectEqualsMethod(123), 123);
        $assertNotEqualsInBothDirections(self::createObjectWithObjectEqualsMethod(123), null);
        $assertNotEqualsInBothDirections(self::createObjectWithObjectEqualsMethod(123), self::createObjectWithObjectEqualsMethod(123.0));

        $assertEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod('abc'), self::createObjectWithNullableStringEqualsMethod('abc'));
        $assertEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod('abc'), 'abc');
        $assertEqualsInBothDirections('abc', self::createObjectWithNullableStringEqualsMethod('abc'));
        $assertEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod(null), self::createObjectWithNullableStringEqualsMethod(null));
        $assertEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod(null), null);
        $assertEqualsInBothDirections(null, self::createObjectWithNullableStringEqualsMethod(null));
        $assertNotEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod('abc'), self::createObjectWithNullableStringEqualsMethod(null));
        $assertNotEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod('abc'), null);
        $assertNotEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod(null), self::createObjectWithNullableStringEqualsMethod('abc'));
        $assertNotEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod(null), 'abc');
        $assertNotEqualsInBothDirections(self::createObjectWithNullableStringEqualsMethod(null), 123);
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @phpstan-param list{TKey, TValue} ...$keyValuePairs
     *
     * @return DsMap<TKey, TValue>
     */
    private static function createDsMapFromPairs(array ...$keyValuePairs): DsMap
    {
        $result = new DsMap();
        foreach ($keyValuePairs as [$key, $value]) {
            $result->put($key, $value);
        }
        return $result;
    }

    /**
     * @template T
     *
     * @param T ...$values
     *
     * @return DsPriorityQueue<T>
     */
    private static function createDsPriorityQueue(mixed ...$values): DsPriorityQueue
    {
        $result = new DsPriorityQueue();
        foreach ($values as $value) {
            $result->push($value, priority: 1);
        }
        return $result; // @phpstan-ignore return.type
    }

    private static function createObjectWithMixedEqualsMethod(mixed $propValue): object
    {
        return new class ($propValue) {
            public function __construct(private readonly mixed $propValue)
            {
            }

            public function equals(mixed $other): bool
            {
                return ($other instanceof self) ? DeepEqual::areEqual($this->propValue, $other->propValue) : DeepEqual::areEqual($this->propValue, $other);
            }
        };
    }

    private static function createObjectWithObjectEqualsMethod(mixed $propValue): object
    {
        return new class ($propValue) {
            public function __construct(private readonly mixed $propValue)
            {
            }

            public function equals(object $other): bool
            {
                return ($other instanceof self) && DeepEqual::areEqual($this->propValue, $other->propValue);
            }
        };
    }

    private static function createObjectWithNullableStringEqualsMethod(?string $propValue): object
    {
        return new class ($propValue) {
            public function __construct(private readonly ?string $propValue)
            {
            }

            public function equals(null|object|string $other): bool
            {
                return ($other instanceof self) ? DeepEqual::areEqual($this->propValue, $other->propValue) : DeepEqual::areEqual($this->propValue, $other);
            }
        };
    }
}
