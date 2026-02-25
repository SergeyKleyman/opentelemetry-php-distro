<?php

/** @noinspection PhpUnusedAliasInspection */

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Closure;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @template T
 *
 * @extends NullableOptionMetadata<T>
 */
final class NullableCustomOptionMetadata extends NullableOptionMetadata
{
    /**
     * @param Closure(string): T $parseFunc
     */
    public function __construct(Closure $parseFunc)
    {
        parent::__construct(new CustomOptionParser($parseFunc));
    }
}
