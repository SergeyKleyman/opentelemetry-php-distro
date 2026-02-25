<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface RawSnapshotInterface
{
    public function valueFor(string $optionName): ?string;
}
