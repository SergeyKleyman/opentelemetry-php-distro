<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends NullableOptionMetadata<int>
 */
final class NullableIntOptionMetadata extends NullableOptionMetadata
{
    public function __construct(?int $minValidValue, ?int $maxValidValue)
    {
        parent::__construct(new IntOptionParser($minValidValue, $maxValidValue));
    }
}
