<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use Composer\InstalledVersions;
use Composer\Semver\Comparator as ComposerSemverComparator;
use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\OTelDistroScoperConfig;
use OpenTelemetry\Distro\Util\BoolUtil;
use OpenTelemetry\DistroTools\Build\BuildToolsUtil;
use OpenTelemetry\DistroTools\Build\ComposerUtil;
use OTelDistroTests\ComponentTests\DependenciesScopingTestApp\App;
use OTelDistroTests\ComponentTests\DependenciesScopingTestApp\Shared;
use OTelDistroTests\ComponentTests\Util\AppCodeAuxOutputUtil;
use OTelDistroTests\ComponentTests\Util\AppCodeHostParams;
use OTelDistroTests\ComponentTests\Util\AppCodeRequestParams;
use OTelDistroTests\ComponentTests\Util\AppCodeTarget;
use OTelDistroTests\ComponentTests\Util\CliScriptAppCodeHostHandle;
use OTelDistroTests\ComponentTests\Util\ComponentTestCaseBase;
use OTelDistroTests\ComponentTests\Util\WaitForOTelSignalCounts;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\DataProviderForTestBuilder;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\JsonUtil;
use OTelDistroTests\Util\Log\SinkForTests;
use OTelDistroTests\Util\MixedMap;

/**
 * @group smoke
 * @group does_not_require_external_services
 */
final class DependenciesScopingTest extends ComponentTestCaseBase
{
    /**
     * @see https://github.com/php-fig/log/blob/2.0.0/src/LoggerTrait.php#L23
     */
    private const PSR_LOG_LAST_MAJOR_VERSION_WITHOUT_RETURN_TYPE = 2;

    /**
     * @see https://github.com/php-fig/log/blob/3.0.0/src/LoggerTrait.php#L23
     */
    private const PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE = 3;

    private const PSR_LOG_MAJOR_VERSIONS = [self::PSR_LOG_LAST_MAJOR_VERSION_WITHOUT_RETURN_TYPE, self::PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE];

    private const PSR_LOG_VERSION_TO_INSTALL_FOR_APP_KEY = 'psr_log_version_to_install_for_app';
    private const OTEL_SDK_VERSION_TO_INSTALL_FOR_APP_KEY = 'otel_sdk_version_to_install_for_app';

    private static function majorToFullVersion(int $majorVersion): string
    {
        return $majorVersion . '.0.0';
    }

    private static function getOTelSdkVersionWithDistro(): string
    {
        return AssertEx::isString(InstalledVersions::getPrettyVersion(Shared::OTEL_SDK_PACKAGE_NAME));
    }

    private static function isPsrLogVersionWithReturnType(string $version): bool
    {
        return ComposerSemverComparator::lessThanOrEqualTo(self::majorToFullVersion(self::PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE), $version);
    }

    public function test0SharedAppConstsInSync(): void
    {
        AssertEx::sameConstValues(OptionForProdName::enabled->name, Shared::DISTRO_ENABLED_CFG_OPT_NAME);
        AssertEx::sameConstValues(OTelDistroScoperConfig::PREFIX, Shared::SCOPING_PREFIX);
        AssertEx::sameConstValues(OptionForProdName::debug_scoper_enabled->name, Shared::DEBUG_SCOPER_ENABLED_CFG_OPT_NAME);

        $expectedAppLogLinePrefix = SinkForTests::LOG_LINE_PREFIX . ' [' . ClassNameUtil::fqToShortFromRawString(__CLASS__) . ClassNameUtil::fqToShort(App::class) . ']';
        /** @noinspection PhpUnitMisorderedAssertEqualsArgumentsInspection */
        self::assertSame($expectedAppLogLinePrefix, Shared::APP_LOG_LINE_PREFIX);
    }

    public function test0SemverComparator(): void
    {
        $assertLessThanOrEqualTo = function (bool $expectedResult, string $lhsVersion, string $rhsVersion): void {
            self::assertSame($expectedResult, ComposerSemverComparator::lessThanOrEqualTo($lhsVersion, $rhsVersion));
        };

        $assertLessThanOrEqualTo(true, '3.0.0', '3.0.0');
        $assertLessThanOrEqualTo(true, '3.0.2', '3.0.2');
        $assertLessThanOrEqualTo(true, '1.0.0', '3.0.0');
        $assertLessThanOrEqualTo(false, '3.0.0', '1.0.0');
        $assertLessThanOrEqualTo(true, '2.0.0', '3.0.0');
        $assertLessThanOrEqualTo(false, '3.0.0', '2.0.0');

        $assertLessThanOrEqualTo(true, self::majorToFullVersion(self::PSR_LOG_LAST_MAJOR_VERSION_WITHOUT_RETURN_TYPE), self::majorToFullVersion(self::PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE));
        $assertLessThanOrEqualTo(false, self::majorToFullVersion(self::PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE), self::majorToFullVersion(self::PSR_LOG_LAST_MAJOR_VERSION_WITHOUT_RETURN_TYPE));
    }

    private static function isScopingEnabledFromPhpUnitContext(): bool
    {
        return self::buildProdConfig()->debugScoperEnabled;
    }

    /**
     * @return iterable<string, array{MixedMap}>
     */
    public static function dataProviderForTestOnAppWithConflict(): iterable
    {
        if (self::isMainAppCodeHostHttp()) {
            return ['dummy data set' => [new MixedMap()]];
        }

        $psrLogVersionsForApp = [];
        foreach (self::PSR_LOG_MAJOR_VERSIONS as $majorVersion) {
            $psrLogVersionsForApp[] = self::majorToFullVersion($majorVersion);
        }

        $otelSdkVersionsForApp = [self::getOTelSdkVersionWithDistro()];
        // TODO: Make the case when the app has incompatible OTel SDK versio work with Distro's vendor not scoped
        if (self::isScopingEnabledFromPhpUnitContext()) {
            // Test OTel SDK version not compatible with the one packaged with the Distro
            $otelSdkVersionsForApp[] = '0.0.17';
        }

        return self::adaptDataProviderForTestBuilderToSmokeToDescToMixedMap(
            (new DataProviderForTestBuilder())
                ->addProdBoolConfigOptionKeyedDimensionAllValuesCombinable(OptionForProdName::enabled->name)
                ->addKeyedDimensionAllValuesCombinable(self::PSR_LOG_VERSION_TO_INSTALL_FOR_APP_KEY, $psrLogVersionsForApp)
                ->addKeyedDimensionAllValuesCombinable(Shared::IS_APP_COMPATIBLE_WITH_PSR_LOG_RETURN_TYPE_ENV_VAR_NAME_SUFFIX, [false, true])
                ->addKeyedDimensionAllValuesCombinable(self::OTEL_SDK_VERSION_TO_INSTALL_FOR_APP_KEY, $otelSdkVersionsForApp)
        );
    }

    /**
     * @param array<string, string> $packageNameToVersion
     */
    private function setPackagesVersionsInComposerJson(array $packageNameToVersion, string $composerJsonFilePath): void
    {
        $logger = self::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__);
        $logDebug = $logger->ifDebugLevelEnabledNoLine(__FUNCTION__);

        $fileContents = BuildToolsUtil::getFileContents($composerJsonFilePath);
        $logDebug?->log(__LINE__, '', compact('composerJsonFilePath', 'fileContents'));
        $decodedJson = AssertEx::isArray(JsonUtil::decode($fileContents));
        $requireSection = AssertEx::isArray(AssertEx::arrayHasKey(ComposerUtil::COMPOSER_JSON_REQUIRE_KEY, $decodedJson));
        $requireSectionUpdated = $requireSection;
        foreach ($packageNameToVersion as $packageName => $packageVersion) {
            self::assertArrayHasKey($packageName, $requireSectionUpdated);
            $requireSectionUpdated[$packageName] = $packageVersion;
        }
        $decodedJsonUpdated[ComposerUtil::COMPOSER_JSON_REQUIRE_KEY] = $requireSectionUpdated;
        $logDebug?->log(__LINE__, '', compact('decodedJsonUpdated'));
        BuildToolsUtil::putFileContents($composerJsonFilePath, JsonUtil::encode($decodedJsonUpdated));
    }

    private function installTestApp(MixedMap $testArgs, string $installedAppDir): void
    {
        $logger = self::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__);
        $logDebug = $logger->ifDebugLevelEnabledNoLine(__FUNCTION__);

        BuildToolsUtil::copyDirectoryContents(FileUtil::partsToPath(__DIR__, 'DependenciesScopingTestApp'), $installedAppDir);
        self::setPackagesVersionsInComposerJson(
            [
                'open-telemetry/sdk' => $testArgs->getString(self::OTEL_SDK_VERSION_TO_INSTALL_FOR_APP_KEY),
                'psr/log' => $testArgs->getString(self::PSR_LOG_VERSION_TO_INSTALL_FOR_APP_KEY),
            ],
            FileUtil::partsToPath($installedAppDir, ComposerUtil::COMPOSER_JSON_FILE_NAME)
        );
        $logDebug?->log(__LINE__, 'Before installing test app');
        BuildToolsUtil::listDirectoryContents($installedAppDir, logLevel: LogLevel::debug);
        BuildToolsUtil::changeCurrentDirectoryRunCodeAndRestore(
            $installedAppDir,
            fn () => ComposerUtil::execComposerInstallShellCommand(withDev: false),
        );
        $logDebug?->log(__LINE__, 'After installing test app');
        BuildToolsUtil::listDirectoryContents($installedAppDir, logLevel: LogLevel::debug);
        BuildToolsUtil::listDirectoryContents(FileUtil::partsToPath($installedAppDir, 'vendor'), recursiveDepth: 1, logLevel: LogLevel::debug);
    }

    private static function dummyAppCodeForTestOnAppWithConflict(): void
    {
        self::fail('This code should not be executed');
    }

    /**
     * @see App::generatePackagesVersions
     */
    private static function verifyPackagesVersions(MixedMap $testArgs, MixedMap $appCodeAuxOutput, bool $isDistroEnabled, string $psrLogVersionToInstallForApp, string $psrLogVersionWithDistro): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $expectedDistroOrAppKeys = [Shared::buildDistroOrAppKey(isDistro: false)];
        if ($isDistroEnabled) {
            $expectedDistroOrAppKeys[] = Shared::buildDistroOrAppKey(isDistro: true);
        }
        $dbgCtx->add(compact('expectedDistroOrAppKeys'));

        $expectedVersionsWithApp = [
            Shared::OTEL_SDK_PACKAGE_NAME => $testArgs->getString(self::OTEL_SDK_VERSION_TO_INSTALL_FOR_APP_KEY),
            Shared::PSR_LOG_PACKAGE_NAME => $psrLogVersionToInstallForApp,
        ];
        AssertEx::equalScalarLists(Shared::ALL_PACKAGE_NAMES, array_keys($expectedVersionsWithApp));
        $expectedVersions = [];
        foreach ($expectedVersionsWithApp as $packageName => $expectedVersionWithApp) {
            self::assertArrayNotHasKey($packageName, $expectedVersions);
            $expectedVersions[$packageName] = [];
            $expectedVersions[$packageName][Shared::buildDistroOrAppKey(isDistro: false)] = $expectedVersionWithApp;
        }
        if ($isDistroEnabled) {
            foreach (Shared::ALL_PACKAGE_NAMES as $packageName) {
                $expectedVersions[$packageName][Shared::buildDistroOrAppKey(isDistro: true)] = InstalledVersions::getPrettyVersion($packageName);
            }

            // Verify that psr/log package version with Distro is equal or higher that the first major version with return type
            self::assertTrue(self::isPsrLogVersionWithReturnType($psrLogVersionWithDistro));
        }

        $packagesVersions = AssertEx::isArray(AssertEx::arrayHasKey(Shared::PACKAGES_VERSIONS_KEY, $appCodeAuxOutput->cloneAsArray()));
        AssertEx::equalScalarLists(Shared::ALL_PACKAGE_NAMES, array_keys($packagesVersions));
        $dbgCtx->pushSubScope();
        foreach (Shared::ALL_PACKAGE_NAMES as $packageName) {
            $dbgCtx->resetTopSubScope(compact('packageName'));
            $distroOrAppToVersion = AssertEx::isArray(AssertEx::arrayHasKey($packageName, $packagesVersions));
            AssertEx::equalScalarLists($expectedDistroOrAppKeys, array_keys($distroOrAppToVersion));
            $dbgCtx->pushSubScope();
            foreach ($expectedDistroOrAppKeys as $distroOrAppKey) {
                $dbgCtx->resetTopSubScope(compact('distroOrAppKey'));
                $expectedVersion = AssertEx::isString(AssertEx::arrayHasKey($distroOrAppKey, AssertEx::arrayHasKey($packageName, $expectedVersions)));
                $dbgCtx->add(compact('expectedVersion'));
                $actualVersion = AssertEx::isString(AssertEx::arrayHasKey($distroOrAppKey, $distroOrAppToVersion));
                $dbgCtx->add(compact('actualVersion'));
                self::assertTrue(ComposerSemverComparator::equalTo($expectedVersion, $actualVersion));
            }
            $dbgCtx->popSubScope();
        }
        $dbgCtx->popSubScope();
    }

    /**
     * @param list<'scoped'|'not scoped'> $expectedScopedKeys
     *
     * @see App::generateClassesSourceCodeFilesPaths
     */
    private static function verifyClassesSourceCodeFilesPaths(MixedMap $appCodeAuxOutput, bool $isDistroEnabled, bool $isScopingEnabled, array $expectedScopedKeys): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $distroVendorDir = $isDistroEnabled ? AssertEx::isString($appCodeAuxOutput[Shared::DISTRO_VENDOR_DIR_PATH_KEY]) : null;
        $appVendorDir = AssertEx::isString($appCodeAuxOutput[Shared::APP_VENDOR_DIR_PATH_KEY]);

        $expectedIsScopedToVendorDir =
            $isDistroEnabled
                ? ($isScopingEnabled
                    ? [Shared::buildScopedKey(false) => $appVendorDir, Shared::buildScopedKey(true) => $distroVendorDir]
                    : [Shared::buildScopedKey(false) => $distroVendorDir])
                : [Shared::buildScopedKey(false) => $appVendorDir];

        $classesSourceCodeFilesPaths = AssertEx::isArray(AssertEx::arrayHasKey(Shared::CLASSES_SOURCE_CODE_FILES_PATHS_KEY, $appCodeAuxOutput->cloneAsArray()));
        AssertEx::equalScalarLists(Shared::ALL_CLASS_NAMES, array_keys($classesSourceCodeFilesPaths));
        $dbgCtx->pushSubScope();
        foreach (Shared::ALL_CLASS_NAMES as $fqClassName) {
            $dbgCtx->resetTopSubScope(compact('fqClassName'));
            $isScopedToFilePath = AssertEx::isArray(AssertEx::arrayHasKey($fqClassName, $classesSourceCodeFilesPaths));
            AssertEx::equalScalarLists($expectedScopedKeys, array_keys($isScopedToFilePath));
            $dbgCtx->pushSubScope();
            foreach ($expectedScopedKeys as $scopedKey) {
                $dbgCtx->resetTopSubScope(compact('scopedKey'));
                self::assertStringStartsWith(
                    AssertEx::isNonEmptyString(AssertEx::arrayHasKey($scopedKey, $expectedIsScopedToVendorDir)),
                    AssertEx::isString(AssertEx::arrayHasKey($scopedKey, $isScopedToFilePath)),
                );
            }
            $dbgCtx->popSubScope();
        }
        $dbgCtx->popSubScope();
    }

    /**
     * @see App::generatePsrLogHasReturnType
     */
    private static function verifyPsrLogHasReturnType(
        MixedMap $appCodeAuxOutput,
        bool $isDistroEnabled,
        bool $isScopingEnabled,
        string $psrLogVersionLoadedByApp,
        string $psrLogVersionWithDistro,
    ): void {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $expectedPsrLogHasReturnType = [Shared::buildScopedKey(false) => self::isPsrLogVersionWithReturnType($psrLogVersionLoadedByApp)];
        if ($isDistroEnabled && $isScopingEnabled) {
            ArrayUtilForTests::addAssertingKeyNew(Shared::buildScopedKey(true), self::isPsrLogVersionWithReturnType($psrLogVersionWithDistro), $expectedPsrLogHasReturnType);
        }

        AssertEx::equalMaps($expectedPsrLogHasReturnType, AssertEx::isArray(AssertEx::arrayHasKey(Shared::PSR_LOG_HAS_RETURN_TYPE_KEY, $appCodeAuxOutput->cloneAsArray())));
    }

    private function implTestOnAppWithConflict(MixedMap $testArgs, string $installedAppDir): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $testCaseHandle = $this->getTestCaseHandle();

        $isDistroEnabled = $testArgs->getBool(OptionForProdName::enabled->name);
        $psrLogVersionToInstallForApp = $testArgs->getString(self::PSR_LOG_VERSION_TO_INSTALL_FOR_APP_KEY);
        $isAppCompatibleWithPsrLogReturnType = $testArgs->getBool(Shared::IS_APP_COMPATIBLE_WITH_PSR_LOG_RETURN_TYPE_ENV_VAR_NAME_SUFFIX);

        /** @var array<string, mixed> $appCodeRequestArgsArr */
        $appCodeRequestArgsArr = $testArgs->cloneAsArray();
        AppCodeAuxOutputUtil::createTempFile(__CLASS__, $testCaseHandle, /* in,out */ $appCodeRequestArgsArr);
        $appCodeRequestArgs = new MixedMap($appCodeRequestArgsArr);

        $appCodeHost = $testCaseHandle->ensureMainAppCodeHost(
            function (AppCodeHostParams $appCodeHostParams) use ($appCodeRequestArgs, $isAppCompatibleWithPsrLogReturnType): void {
                self::ensureTransactionSpanEnabled($appCodeHostParams);
                self::copyProdOptionsToAppCodeHostParams($appCodeRequestArgs, $appCodeHostParams);
                $appCodeHostParams->addEnvVar(Shared::buildEnvVarName(Shared::APP_CODE_AUX_OUTPUT_FILE_PATH_ENV_VAR_NAME_SUFFIX), AppCodeAuxOutputUtil::getFilePath($appCodeRequestArgs));
                $appCodeHostParams->addEnvVar(
                    Shared::buildEnvVarName(Shared::IS_APP_COMPATIBLE_WITH_PSR_LOG_RETURN_TYPE_ENV_VAR_NAME_SUFFIX),
                    BoolUtil::toString($isAppCompatibleWithPsrLogReturnType)
                );
                $appCodeHostParams->addEnvVar(
                    Shared::buildEnvVarName(Shared::IS_DEBUG_LOG_ENABLED_ENV_VAR_NAME_SUFFIX),
                    BoolUtil::toString(AmbientContextForTests::loggerFactory()->isEnabledForLevel(LogLevel::debug))
                );
            }
        );

        CliScriptAppCodeHostHandle::setScriptToRun(FileUtil::partsToPath($installedAppDir, 'run.php'));

        $appCodeProcessExitCode = $appCodeHost->execAppCode(
            AppCodeTarget::asRouted([__CLASS__, 'dummyAppCodeForTestOnAppWithConflict']),
            function (AppCodeRequestParams $appCodeReqParams): void {
                // Component Tests infrastructure should not fail on non-zero App code process exit code
                // which will be verified below
                $appCodeReqParams->setExpectedAppCodeProcessExitCode(null);
            }
        );
        $dbgCtx->add(compact('appCodeProcessExitCode'));
        self::assertNotNull($appCodeProcessExitCode);

        if ($isDistroEnabled) {
            $agentBackendComms = $testCaseHandle->waitForEnoughAgentBackendComms(WaitForOTelSignalCounts::spans(1)); // exactly 1 span (the root span) is expected
            $dbgCtx->add(compact('agentBackendComms'));
        }

        // Assert

        $appCodeAuxOutput = AppCodeAuxOutputUtil::readDataAsMixedMapFromTempFile($appCodeRequestArgsArr);
        $dbgCtx->add(compact('appCodeAuxOutput'));

        self::assertSame($isDistroEnabled, $appCodeAuxOutput->getBool(Shared::DISTRO_ENABLED_CFG_OPT_NAME));
        $isScopingEnabled = $appCodeAuxOutput->getBool(Shared::DEBUG_SCOPER_ENABLED_CFG_OPT_NAME);

        $expectedIsScopedVariants = [false];
        if ($isDistroEnabled && $isScopingEnabled) {
            $expectedIsScopedVariants[] = true;
        }
        $expectedScopedKeys = array_map(fn($isScoped) => Shared::buildScopedKey($isScoped), $expectedIsScopedVariants);
        sort(/* ref */ $expectedScopedKeys);
        /** @var list<'scoped'|'not scoped'> $expectedScopedKeys */
        $dbgCtx->add(compact('expectedIsScopedVariants', 'expectedScopedKeys'));

        $psrLogVersionWithDistro = AssertEx::isString(InstalledVersions::getPrettyVersion(Shared::PSR_LOG_PACKAGE_NAME));

        self::verifyPackagesVersions($testArgs, $appCodeAuxOutput, $isDistroEnabled, $psrLogVersionToInstallForApp, $psrLogVersionWithDistro);

        self::verifyClassesSourceCodeFilesPaths($appCodeAuxOutput, $isDistroEnabled, $isScopingEnabled, $expectedScopedKeys);

        $psrLogVersionLoadedByApp = ($isDistroEnabled && !$isScopingEnabled) ? $psrLogVersionWithDistro : $psrLogVersionToInstallForApp;

        self::verifyPsrLogHasReturnType($appCodeAuxOutput, $isDistroEnabled, $isScopingEnabled, $psrLogVersionLoadedByApp, $psrLogVersionWithDistro);

        // App's use of psr/log is expected to fail if and only if psr/log version loaded by App has return type but App is configured to be incompatible with return type
        $dbgCtx->add(compact('psrLogVersionLoadedByApp'));
        $isPsrLogLoadedByAppHasReturnType = ComposerSemverComparator::lessThanOrEqualTo(self::majorToFullVersion(self::PSR_LOG_FIRST_MAJOR_VERSION_WITH_RETURN_TYPE), $psrLogVersionLoadedByApp);
        $dbgCtx->add(compact('isPsrLogLoadedByAppHasReturnType'));
        $isUsePsrLogExpectedToFail = $isPsrLogLoadedByAppHasReturnType && !$isAppCompatibleWithPsrLogReturnType;
        $dbgCtx->add(compact('isUsePsrLogExpectedToFail'));
        self::assertSame($isUsePsrLogExpectedToFail, $appCodeProcessExitCode !== 0);
    }

    /**
     * @dataProvider dataProviderForTestOnAppWithConflict
     */
    public function testOnAppWithConflict(MixedMap $testArgs): void
    {
        if (self::skipIfMainAppCodeHostIsNotCliScript()) {
            return;
        }

        $appsCodeScriptToRestore = CliScriptAppCodeHostHandle::getScriptToRun();
        try {
            self::runAndEscalateLogLevelOnFailure(
                self::buildDbgDescForTestWithArgs(__CLASS__, __FUNCTION__, $testArgs),
                function () use ($testArgs): void {
                    BuildToolsUtil::runCodeOnUniqueNameTempDir(
                        tempDirNamePrefix: FileUtil::generateTempFileNamePrefix(ClassNameUtil::fqToShortFromRawString(__CLASS__) . '_app'),
                        code: function (string $installedAppDir) use ($testArgs): void {
                            $this->installTestApp($testArgs, $installedAppDir);
                            $this->implTestOnAppWithConflict($testArgs, $installedAppDir);
                        },
                    );
                }
            );
        } finally {
            CliScriptAppCodeHostHandle::setScriptToRun($appsCodeScriptToRestore);
        }
    }
}
