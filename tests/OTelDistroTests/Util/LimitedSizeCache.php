<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\ArrayUtil;
use PHPUnit\Framework\Assert;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @template TKey of array-key
 * @template TValue of mixed
 */
final class LimitedSizeCache
{
    /** @var array<TKey, TValue> */
    private array $keyToValue = [];

    /**
     * @param non-negative-int $countLowWaterMark
     * @param non-negative-int $countHighWaterMark
     */
    public function __construct(
        private readonly int $countLowWaterMark,
        private readonly int $countHighWaterMark,
    ) {
        Assert::assertGreaterThan($countLowWaterMark, $countHighWaterMark);
    }

    /**
     * @phpstan-param TKey   $key
     * @phpstan-param TValue $value
     */
    public function put(string|int $key, mixed $value): void
    {
        $cacheCount = count($this->keyToValue);
        if ($cacheCount > $this->countHighWaterMark) {
            // Keep the last countLowWaterMark entries
            $this->keyToValue = array_slice(array: $this->keyToValue, offset: $cacheCount - $this->countLowWaterMark);
        }

        // Remove the key if it already exists  to make the new entry the last in added order
        if (array_key_exists($key, $this->keyToValue)) {
            unset($this->keyToValue[$key]);
        }
        $this->keyToValue[$key] = $value;
    }

    /**
     * @phpstan-param TKey                   $key
     * @phpstan-param callable(TKey): TValue $computeValue
     *
     * @return TValue
     */
    public function getIfCachedElseCompute(string|int $key, callable $computeValue): mixed
    {
        if (ArrayUtil::getValueIfKeyExists($key, $this->keyToValue, /* out */ $valueInCache)) {
            return $valueInCache;
        }

        $value = $computeValue($key);
        $this->put($key, $value);
        return $value;
    }
}
