<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionWithDefaultValueMetadata<string>
 *
 * @noinspection PhpUnused
 */
final class StringOptionMetadata extends OptionWithDefaultValueMetadata
{
    public function __construct(string $defaultValue)
    {
        parent::__construct(new StringOptionParser(), $defaultValue);
    }
}
