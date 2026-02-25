<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionWithDefaultValueMetadata<bool>
 */
final class BoolOptionMetadata extends OptionWithDefaultValueMetadata
{
    public function __construct(bool $defaultValue)
    {
        parent::__construct(new BoolOptionParser(), $defaultValue);
    }
}
