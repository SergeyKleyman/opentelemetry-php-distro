<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class CompositeRawSnapshotSource implements RawSnapshotSourceInterface
{
    /** @var array<RawSnapshotSourceInterface> */
    private array $subSources;

    /**
     * @param array<RawSnapshotSourceInterface> $subSources
     */
    public function __construct(array $subSources)
    {
        $this->subSources = $subSources;
    }

    /** @inheritDoc */
    #[Override]
    public function currentSnapshot(array $optionNameToMeta): RawSnapshotInterface
    {
        $subSnapshots = [];
        foreach ($this->subSources as $subSource) {
            $subSnapshots[] = $subSource->currentSnapshot($optionNameToMeta);
        }
        return new CompositeRawSnapshot($subSnapshots);
    }
}
