<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use InvalidArgumentException;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class CombinatorialUtil
{
    use StaticClassTrait;

    /**
     * @param array<mixed> $totalSet
     * @param int          $subSetSize
     *
     * @return iterable<array<mixed>>
     */
    public static function permutations(array $totalSet, int $subSetSize): iterable
    {
        if ($subSetSize > count($totalSet)) {
            throw new InvalidArgumentException(
                '$subSetSize should not be greater than $totalSet count.'
                . ' $totalSet count:' . count($totalSet) . '.'
                . ' $subSetSize:' . $subSetSize . '.'
            );
        }

        if ($subSetSize < 0) {
            throw new InvalidArgumentException(
                '$subSetSize should not be negative.'
                . ' $subSetSize:' . $subSetSize . '.'
            );
        }

        if ($subSetSize === 0) {
            yield [];
            return;
        }

        foreach (RangeUtil::generateUpTo(count($totalSet)) as $firstIndex) {
            $newTotalSet = $totalSet;
            // remove the first element from $newTotalSet
            array_splice(/* ref */ $newTotalSet, $firstIndex, 1);
            foreach (self::permutations($newTotalSet, $subSetSize - 1) as $permutation) {
                array_unshift(/* ref */ $permutation, $totalSet[$firstIndex]);
                yield $permutation;
            }
        }
    }

    /**
     * @param array<mixed>                  $values
     * @param array<mixed, iterable<mixed>> $restOfIterables
     *
     * @return iterable<array<mixed>>
     */
    private static function cartesianProductImpl(array $values, array $restOfIterables): iterable
    {
        if (count($restOfIterables) === 0) {
            yield $values;
            return;
        }

        $restOfIterablesForChildCalls = array_slice($restOfIterables, 1);
        $currentIterableAsArray = array_slice($restOfIterables, 0, 1);
        foreach ($currentIterableAsArray as $currentIterableKey => $currentIterable) {
            foreach ($currentIterable as $value) {
                yield from self::cartesianProductImpl(
                    array_merge($values, [$currentIterableKey => $value]),
                    $restOfIterablesForChildCalls
                );
            }
        }
    }

    /**
     * @param array<mixed, iterable<mixed>> $iterables
     *
     * @return iterable<array<mixed>>
     */
    public static function cartesianProduct(array $iterables): iterable
    {
        yield from self::cartesianProductImpl([], $iterables);
    }
}
