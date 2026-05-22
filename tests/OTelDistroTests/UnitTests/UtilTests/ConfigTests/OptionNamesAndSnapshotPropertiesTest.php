<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OpenTelemetry\Distro\Util\TextUtil;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\Config\ConfigSnapshotForProd;
use OTelDistroTests\Util\Config\ConfigSnapshotForTests;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\Config\OptionForTestsName;
use OTelDistroTests\Util\Config\OptionMetadata;
use OTelDistroTests\Util\Config\OptionsForProdMetadata;
use OTelDistroTests\Util\Config\OptionsForTestsMetadata;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\Log\LoggableToString;
use OTelDistroTests\Util\TestCaseBase;
use OTelDistroTests\Util\TextUtilForTests;

/**
 * @phpstan-type ConfigKind 'prod'|'tests'
 * @phpstan-type ConfigSnapshot ConfigSnapshotForProd|ConfigSnapshotForTests
 */
class OptionNamesAndSnapshotPropertiesTest extends TestCaseBase
{
    private const PROD_CONFIG_KIND = 'prod';
    private const TESTS_CONFIG_KIND = 'tests';
    private const ALL_CONFIG_KINDS = [self::PROD_CONFIG_KIND, self::TESTS_CONFIG_KIND];

    /**
     * @param ConfigKind $configKind
     */
    private static function assertValidConfigKind(string $configKind): void
    {
        if (in_array($configKind, self::ALL_CONFIG_KINDS, /* strict: */ true)) {
            return;
        }

        self::fail(LoggableToString::convertMessageAndContext('Unknown config kind', compact('configKind')));
    }

    /**
     * @return iterable<array{ConfigKind}>
     */
    public static function dataProviderGeneratingConfigKind(): iterable
    {
        foreach (self::ALL_CONFIG_KINDS as $configKind) {
            yield [$configKind];
        }
    }

    /**
     * @param ConfigKind $configKind
     *
     * @return class-string<OptionForProdName>|class-string<OptionForTestsName>
     */
    private static function getNameEnumClass(string $configKind): string
    {
        self::assertValidConfigKind($configKind);

        return match ($configKind) {
            self::PROD_CONFIG_KIND => OptionForProdName::class,
            self::TESTS_CONFIG_KIND => OptionForTestsName::class,
        };
    }

    /**
     * @param ConfigKind $configKind
     *
     * @return array<string, OptionMetadata<mixed>>
     */
    private static function getOptionsMetadata(string $configKind): array
    {
        self::assertValidConfigKind($configKind);

        return match ($configKind) {
            self::PROD_CONFIG_KIND => OptionsForProdMetadata::get(),
            self::TESTS_CONFIG_KIND => OptionsForTestsMetadata::get(),
        };
    }

    /**
     * @param ConfigKind $configKind
     *
     * @return list<string>
     */
    private static function getSnapshotPropertiesNamesForOptions(string $configKind): array
    {
        self::assertValidConfigKind($configKind);

        return match ($configKind) {
            self::PROD_CONFIG_KIND => ConfigSnapshotForProd::propertyNamesForOptions(),
            self::TESTS_CONFIG_KIND => ConfigSnapshotForTests::propertyNamesForOptions(),
        };
    }

    /**
     * @param ConfigKind $configKind
     * @param array<string, mixed> $optNameToParsedValue
     *
     * @return ConfigSnapshot
     */
    private static function newSnapshot(string $configKind, array $optNameToParsedValue): ConfigSnapshotForProd|ConfigSnapshotForTests
    {
        self::assertValidConfigKind($configKind);

        return match ($configKind) {
            self::PROD_CONFIG_KIND => new ConfigSnapshotForProd($optNameToParsedValue),
            self::TESTS_CONFIG_KIND => new ConfigSnapshotForTests($optNameToParsedValue),
        };
    }

    /**
     * @dataProvider dataProviderGeneratingConfigKind
     *
     * @param ConfigKind $configKind
     */
    public function testOptionNamesAndMetadataMapMatch(string $configKind): void
    {
        $optNameCases = self::getNameEnumClass($configKind)::cases();
        $optMetas = self::getOptionsMetadata($configKind);

        $optNamesFromCases = array_map(fn($optNameCase) => $optNameCase->name, $optNameCases);
        sort(/* ref */ $optNamesFromCases);
        $optNamesFromMetas = array_keys($optMetas);
        sort(/* ref */ $optNamesFromMetas);
        AssertEx::arraysHaveTheSameContent($optNamesFromCases, $optNamesFromMetas);
    }

    /**
     * @dataProvider dataProviderGeneratingConfigKind
     *
     * @param ConfigKind $configKind
     */
    public function testOptionNamesAndSnapshotPropertiesMatch(string $configKind): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $optNameCases = self::getNameEnumClass($configKind)::cases();
        $propertyNamesForOptions = self::getSnapshotPropertiesNamesForOptions($configKind);

        $remainingSnapPropNames = $propertyNamesForOptions;
        $dbgCtx->pushSubScope();
        foreach ($optNameCases as $optNameCase) {
            $dbgCtx->resetTopSubScope(compact('optNameCase', 'remainingSnapPropNames'));
            self::assertTrue(ArrayUtilForTests::removeFirstByValue(/* in,out */ $remainingSnapPropNames, TextUtilForTests::snakeToCamelCase($optNameCase->name)));
        }
        $dbgCtx->popSubScope();

        self::assertEmpty($remainingSnapPropNames);
    }

    /**
     * @dataProvider dataProviderGeneratingConfigKind
     *
     * @param ConfigKind $configKind
     */
    public function testSnapshotCanBeAssignedDefaults(string $configKind): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $optNameEnumClass = self::getNameEnumClass($configKind);
        $optNameCases = $optNameEnumClass::cases();
        $optNameToMetadata = self::getOptionsMetadata($configKind);
        $optNameToDefaultValue = [];
        $dbgCtx->pushSubScope();
        foreach ($optNameToMetadata as $optName => $optMeta) {
            $dbgCtx->resetTopSubScope(compact('optName', 'optMeta'));
            ArrayUtilForTests::addAssertingKeyNew($optName, $optMeta->defaultValue(), /* ref */ $optNameToDefaultValue);
        }
        $dbgCtx->popSubScope();

        $configSnapshot = self::newSnapshot($configKind, $optNameToDefaultValue);

        $dbgCtx->pushSubScope();
        foreach ($optNameCases as $optNameCase) {
            $dbgCtx->resetTopSubScope(compact('optNameCase'));
            self::assertSame($optNameToDefaultValue[$optNameCase->name], $configSnapshot->getOptionValueByName($optNameCase));
        }
        $dbgCtx->popSubScope();
    }

    /**
     * @dataProvider dataProviderGeneratingConfigKind
     *
     * @param ConfigKind $configKind
     */
    public function testOptionNameToEnvVarName(string $configKind): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $optNameEnumCases = self::getNameEnumClass($configKind)::cases();
        $dbgCtx->pushSubScope();
        foreach ($optNameEnumCases as $optName) {
            $dbgCtx->resetTopSubScope(compact('optName'));
            $envVarName = $optName->toEnvVarName();
            $dbgCtx->add(compact('envVarName'));
            self::assertTrue(TextUtil::isSuffixOf(strtoupper($optName->name), $envVarName));
        }
        $dbgCtx->popSubScope();
    }

    public function testProdOptionNameToEnvVar(): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $dbgCtx->pushSubScope();
        foreach (OptionForProdName::cases() as $optName) {
            $dbgCtx->resetTopSubScope(compact('optName'));
            $envVarNamePrefix = $optName->getEnvVarNamePrefix();
            $envVarName = $optName->toEnvVarName();
            self::assertStringStartsWith($envVarNamePrefix, $envVarName);
        }
        $dbgCtx->popSubScope();
    }
}
