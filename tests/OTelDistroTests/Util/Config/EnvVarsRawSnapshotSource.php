<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Util\ArrayUtil;
use OTelDistroTests\Util\IterableUtil;
use Override;
use PHPUnit\Framework\Assert;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class EnvVarsRawSnapshotSource implements RawSnapshotSourceInterface
{
    /** @var array<string, string> */
    private readonly array $limitToOptionNameToEnvVarName;

    /**
     * @param string           $envVarNamesPrefix
     * @param iterable<string> $limitToOptionNames
     */
    public function __construct(string $envVarNamesPrefix, iterable $limitToOptionNames)
    {
        $limitToOptionNameToEnvVarName = [];
        foreach ($limitToOptionNames as $optName) {
            $envVarName = self::optionNameToEnvVarName($envVarNamesPrefix, $optName);
            Assert::assertArrayNotHasKey($envVarName, $limitToOptionNameToEnvVarName);
            $limitToOptionNameToEnvVarName[$optName] = $envVarName;
        }
        $this->limitToOptionNameToEnvVarName = $limitToOptionNameToEnvVarName;
    }

    public static function optionNameToEnvVarName(string $envVarNamesPrefix, string $optionName): string
    {
        return $envVarNamesPrefix . strtoupper($optionName);
    }

    /** @inheritDoc */
    #[Override]
    public function currentSnapshot(array $optionNameToMeta): RawSnapshotInterface
    {
        /** @var array<string, string> $optionNameToEnvVarValue */
        $optionNameToEnvVarValue = [];

        foreach (IterableUtil::keys($optionNameToMeta) as $optionName) {
            if (!ArrayUtil::getValueIfKeyExists($optionName, $this->limitToOptionNameToEnvVarName, /* out */ $envVarName)) {
                continue;
            }
            $envVarValue = getenv($envVarName);
            if ($envVarValue !== false) {
                $optionNameToEnvVarValue[$optionName] = $envVarValue;
            }
        }

        return new RawSnapshotFromArray($optionNameToEnvVarValue);
    }
}
