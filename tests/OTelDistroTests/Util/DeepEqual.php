<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use ArrayAccess;
use Countable;
use Ds\PriorityQueue as DsPriorityQueue;
use Ds\Set as DsSet;
use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\Log\LoggableToString;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use Traversable;

/**
 * @phpstan-type AssertTrue callable(bool): bool
 */
final class DeepEqual
{
    use StaticClassTrait;

    /**
     * @phpstan-param ArrayAccess<mixed, mixed>&Traversable<mixed, mixed> $lhs
     * @phpstan-param ArrayAccess<mixed, mixed>&Traversable<mixed, mixed> $rhs
     * @phpstan-param AssertTrue $assertTrue
     */
    private static function areEqualArrayAccessAndTraversableEx(ArrayAccess $lhs, ArrayAccess $rhs, callable $assertTrue): bool
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $dbgCtx->pushSubScope();
        for (
            $lhsIterator = IterableUtil::iterableToIterator($lhs), $rhsIterator = IterableUtil::iterableToIterator($rhs), $count = 0;
            $lhsIterator->valid() && $rhsIterator->valid();
            $lhsIterator->next(), $rhsIterator->next(), ++$count
        ) {
            $lhsKey = $lhsIterator->key();
            $dbgCtx->resetTopSubScope(compact('count', 'lhsKey') + ['$lhsIterator->current()' => $lhsIterator->current()]);
            if (!$assertTrue($rhs->offsetExists($lhsKey))) {
                return false;
            }
            if (!self::areEqualEx($lhsIterator->current(), $rhs->offsetGet($lhsKey), $assertTrue)) {
                return false;
            }
        }
        $dbgCtx->popSubScope();

        $dbgCtx->add(compact('count'));
        if ($lhsIterator->valid()) {
            $dbgCtx->add(['$lhsIterator->key()' => $lhsIterator->key(), '$lhsIterator->current()' => $lhsIterator->current()]);
        }
        if ($rhsIterator->valid()) {
            $dbgCtx->add(['$rhsIterator->key()' => $lhsIterator->key(), '$rhsIterator->current()' => $rhsIterator->current()]);
        }
        if (!$assertTrue($lhsIterator->valid() === $rhsIterator->valid())) {
            return false;
        }

        return true;
    }

    /**
     * @phpstan-param Traversable<mixed, mixed> $lhs
     * @phpstan-param Traversable<mixed, mixed> $rhs
     * @phpstan-param AssertTrue $assertTrue
     */
    private static function areEqualTraversableEx(Traversable $lhs, Traversable $rhs, callable $assertTrue): bool
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $dbgCtx->pushSubScope();
        for (
            $lhsIterator = IterableUtil::iterableToIterator($lhs), $rhsIterator = IterableUtil::iterableToIterator($rhs);
            $lhsIterator->valid() || $rhsIterator->valid();
            $lhsIterator->next(), $rhsIterator->next()
        ) {
            $dbgCtx->resetTopSubScope();
            if ($lhsIterator->valid()) {
                $dbgCtx->add(['$lhsIterator->key()' => $lhsIterator->key(), '$lhsIterator->current()' => $lhsIterator->current()]);
            }
            if ($rhsIterator->valid()) {
                $dbgCtx->add(['$rhsIterator->key()' => $rhsIterator->key(), '$rhsIterator->current()' => $rhsIterator->current()]);
            }
            if (!$assertTrue($lhsIterator->valid() === $rhsIterator->valid())) {
                return false;
            }
            if (!self::areEqualEx($lhsIterator->key(), $rhsIterator->key(), $assertTrue)) {
                return false;
            }
            if (!self::areEqualEx($lhsIterator->current(), $rhsIterator->current(), $assertTrue)) {
                return false;
            }
        }
        $dbgCtx->popSubScope();
        return true;
    }

    /**
     * @phpstan-param AssertTrue $assertTrue
     */
    private static function areEqualObjectsEx(object $lhs, object $rhs, callable $assertTrue): bool
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        if (($lhs instanceof Countable) && ($rhs instanceof Countable)) {
            $lhsCount = $lhs->count();
            $rhsCount = $rhs->count();
            $dbgCtx->add(compact('lhsCount', 'rhsCount'));
            if (!$assertTrue($lhsCount === $rhsCount)) {
                return false;
            }
        }

        // \Ds\Set is a special case because its implementation of ArrayAccess::offsetExists() throws
        if (($lhs instanceof DsSet) && ($rhs instanceof DsSet)) {
            $diff = $lhs->diff($rhs);
            $dbgCtx->add(compact('diff'));
            return $assertTrue($diff->isEmpty());
        }

        // \Ds\PriorityQueue is a special case because iterating over it is destructive, equivalent to successive pop operations until the queue is empty.
        if (($lhs instanceof DsPriorityQueue) && ($rhs instanceof DsPriorityQueue)) {
            return self::areEqualTraversableEx($lhs->copy(), $rhs->copy(), $assertTrue);
        }

        if ($lhs instanceof Traversable) {
            /** @var Traversable<mixed, mixed> $lhs */
            if (!$assertTrue($rhs instanceof Traversable)) {
                return false;
            }
            /** @var Traversable<mixed, mixed> $rhs */

            if ($lhs instanceof ArrayAccess) {
                /** @var ArrayAccess<mixed, mixed>&Traversable<mixed, mixed> $lhs */
                if (!$assertTrue($rhs instanceof ArrayAccess)) {
                    return false;
                }
                /** @var ArrayAccess<mixed, mixed>&Traversable<mixed, mixed> $rhs */

                return self::areEqualArrayAccessAndTraversableEx($lhs, $rhs, $assertTrue);
            }

            return self::areEqualTraversableEx($lhs, $rhs, $assertTrue);
        }


        $dbgCtx->add(['get_class($lhs)' => get_class($lhs), 'get_class($rhs)' => get_class($rhs)]);
        if (!$assertTrue(get_class($lhs) === get_class($rhs))) {
            return false;
        }

        $dbgCtx->pushSubScope();
        foreach (get_object_vars($lhs) as $lhsObjectPropName => $lhsObjectPropValue) {
            $dbgCtx->resetTopSubScope(compact('lhsObjectPropName', 'lhsObjectPropValue'));
            if (!$assertTrue(property_exists($rhs, $lhsObjectPropName))) {
                return false;
            }
            if (!self::areEqualEx($lhsObjectPropValue, $rhs->$lhsObjectPropName, $assertTrue)) {
                return false;
            }
        }
        $dbgCtx->popSubScope();
        return true;
    }

    private const EQUALS_METHOD_NAME = 'equals';

    private static function tryToUseSuitableEqualsMethod(object $thisObj, mixed $other): ?bool
    {
        if (!method_exists($thisObj, self::EQUALS_METHOD_NAME)) {
            return null;
        }

        $method = (new ReflectionClass($thisObj))->getMethod(self::EQUALS_METHOD_NAME);

        if ((($methodReturnType = $method->getReturnType()) === null) || (!ReflectionUtil::areEqualReflectionTypes($methodReturnType, ReflectionUtil::boolReflectionType()))) {
            return null;
        }

        $methodParams = $method->getParameters();
        if (count($methodParams) !== 1) {
            return null;
        }

        $methodParamType = ArrayUtilForTests::getFirstValue($methodParams)->getType();
        if (($methodParamType !== null) && (!ReflectionUtil::canValueBeAssignedToReflectionType($other, $methodParamType))) {
            return null;
        }

        return AssertEx::isBool($method->invoke($thisObj, $other));
    }

    /**
     * @phpstan-param AssertTrue $assertTrue
     */
    public static function areEqualEx(mixed $lhs, mixed $rhs, callable $assertTrue): bool
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        if (is_object($lhs) && (($equalsRetVal = self::tryToUseSuitableEqualsMethod($lhs, $rhs)) !== null)) {
            return $assertTrue($equalsRetVal);
        }

        if (is_object($rhs) && (($equalsRetVal = self::tryToUseSuitableEqualsMethod($rhs, $lhs)) !== null)) {
            return $assertTrue($equalsRetVal);
        }

        if (is_object($lhs)) {
            if (!$assertTrue(is_object($rhs))) {
                return false;
            }
            /** @var object $rhs */

            return self::areEqualObjectsEx($lhs, $rhs, $assertTrue);
        }

        if (($lhs === null) || is_scalar($lhs) || is_resource($lhs)) {
            return $assertTrue($lhs === $rhs);
        }

        if (is_array($lhs)) {
            if (!$assertTrue(is_array($rhs))) {
                return false;
            }
            /** @var array<array-key, mixed> $rhs */

            $lhsCount = count($lhs);
            $rhsCount = count($rhs);
            $dbgCtx->add(compact('lhsCount', 'rhsCount'));
            if (!$assertTrue($lhsCount === $rhsCount)) {
                return false;
            }

            $dbgCtx->pushSubScope();
            foreach ($lhs as $lhsArrayElementKey => $lhsArrayElementValue) {
                $dbgCtx->resetTopSubScope(compact('lhsArrayElementKey', 'lhsArrayElementValue'));
                if (!$assertTrue(array_key_exists($lhsArrayElementKey, $rhs))) {
                    return false;
                }
                if (!self::areEqualEx($lhsArrayElementValue, $rhs[$lhsArrayElementKey], $assertTrue)) {
                    return false;
                }
            }
            $dbgCtx->popSubScope();
            return true;
        }

        Assert::fail(LoggableToString::convertMessageAndContext('Unlhs $lhs type: ' . get_debug_type($lhs), compact('lhs', 'rhs')));
    }

    public static function areEqual(mixed $lhs, mixed $rhs): bool
    {
        return self::areEqualEx($lhs, $rhs, assertTrue: fn(bool $condition) => $condition);
    }

    /**
     * @template T
     *
     * @param list<T> $array
     * @param T $value
     */
    public static function doesArrayContainEqualValue(array $array, mixed $value): bool
    {
        foreach ($array as $arrayElement) {
            if (self::areEqual($arrayElement, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @template T
     *
     * @param list<T> $lhs
     * @param list<T> $rhs
     * @param AssertTrue $assertTrue
     *
     * @return bool
     */
    public static function areEqualAsSetsEx(array $lhs, array $rhs, callable $assertTrue): bool
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $dbgCtx->pushSubScope();
        foreach ($rhs as $rhsElement) {
            $dbgCtx->resetTopSubScope(compact('rhsElement'));
            if (!$assertTrue(self::doesArrayContainEqualValue($lhs, $rhsElement))) {
                return false;
            }
        }
        $dbgCtx->popSubScope();
        return true;
    }
}
