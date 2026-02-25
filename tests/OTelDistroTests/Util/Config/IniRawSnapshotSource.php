<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Util\ArrayUtil;
use Override;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class IniRawSnapshotSource implements RawSnapshotSourceInterface
{
    public const DEFAULT_PREFIX = 'opentelemetry_distro.';

    private string $iniNamesPrefix;

    /**
     * @param string $iniNamesPrefix
     */
    public function __construct(string $iniNamesPrefix)
    {
        $this->iniNamesPrefix = $iniNamesPrefix;
    }

    public static function optionNameToIniName(string $iniNamesPrefix, string $optionName): string
    {
        return $iniNamesPrefix . $optionName;
    }

    /** @inheritDoc */
    #[Override]
    public function currentSnapshot(array $optionNameToMeta): RawSnapshotInterface
    {
        /** @var array<string, string> $optionNameToValue */
        $optionNameToValue = [];

        /** @var array<string, mixed> $allOpts */
        $allOpts = ini_get_all(extension: null, details: false);

        foreach ($optionNameToMeta as $optionName => $optionMeta) {
            $iniName = self::optionNameToIniName($this->iniNamesPrefix, $optionName);
            if (($iniValue = ArrayUtil::getValueIfKeyExistsElse($iniName, $allOpts, null)) !== null) {
                /** @var bool|float|int|string $iniValue */
                $optionNameToValue[$optionName] = self::iniValueToString($iniValue);
            }
        }

        return new RawSnapshotFromArray($optionNameToValue);
    }

    /**
     * @param bool|float|int|string $iniValue
     */
    private static function iniValueToString(mixed $iniValue): string
    {
        if (is_bool($iniValue)) {
            return $iniValue ? 'true' : 'false';
        }

        return strval($iniValue);
    }
}
