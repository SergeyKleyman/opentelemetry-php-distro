<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class CompositeRawSnapshot implements RawSnapshotInterface
{
    /** @var array<RawSnapshotInterface> */
    private array $subSnapshots;

    /**
     * @param array<RawSnapshotInterface> $subSnapshots
     */
    public function __construct(array $subSnapshots)
    {
        $this->subSnapshots = $subSnapshots;
    }

    public function valueFor(string $optionName): ?string
    {
        foreach ($this->subSnapshots as $subSnapshot) {
            if (($value = $subSnapshot->valueFor($optionName)) !== null) {
                return $value;
            }
        }
        return null;
    }
}
