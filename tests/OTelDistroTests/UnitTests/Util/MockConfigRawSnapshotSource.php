<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\Util;

use OTelDistroTests\Util\Config\RawSnapshotFromArray;
use OTelDistroTests\Util\Config\RawSnapshotInterface;
use OTelDistroTests\Util\Config\RawSnapshotSourceInterface;
use Override;

final class MockConfigRawSnapshotSource implements RawSnapshotSourceInterface
{
    /** @var array<string, string> */
    private array $optNameToRawValue = [];

    public function set(string $optName, string $optVal): self
    {
        $this->optNameToRawValue[$optName] = $optVal;
        return $this;
    }

    /** @inheritDoc */
    #[Override]
    public function currentSnapshot(array $optionNameToMeta): RawSnapshotInterface
    {
        return new RawSnapshotFromArray($this->optNameToRawValue);
    }
}
