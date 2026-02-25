<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
interface ArrayReadInterface
{
    /**
     * @phpstan-param TKey $key
     */
    public function keyExists(string|int $key): bool;

    /**
     * @phpstan-param TKey $key
     *
     * @return TValue
     */
    public function getValue(string|int $key): mixed;
}
