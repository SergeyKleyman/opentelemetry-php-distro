<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OTelDistroTests\Util\Duration;
use OTelDistroTests\Util\DurationUnit;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionWithDefaultValueMetadata<Duration>
 */
final class DurationOptionMetadata extends OptionWithDefaultValueMetadata
{
    public function __construct(?Duration $minValidValue, ?Duration $maxValidValue, DurationUnit $defaultUnit, Duration $defaultValue)
    {
        parent::__construct(new DurationOptionParser($minValidValue, $maxValidValue, $defaultUnit), $defaultValue);
    }
}
