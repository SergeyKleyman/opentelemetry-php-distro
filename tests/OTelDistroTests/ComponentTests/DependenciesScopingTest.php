<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use Composer\InstalledVersions;
use Composer\Semver\Comparator as ComposerSemverComparator;
use OTelDistroTests\ComponentTests\Util\AgentBackendComms;
use OTelDistroTests\ComponentTests\Util\AppCodeAuxOutputUtil;
use OTelDistroTests\ComponentTests\Util\CliScriptAppCodeHostHandle;
use OTelDistroTests\ComponentTests\Util\ComponentTestCaseBase;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\DataProviderForTestBuilder;
use OTelDistroTests\Util\DebugContextScopeRef;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\MixedMap;

/**
 * @group smoke
 * @group does_not_require_external_services
 */
final class DependenciesScopingTest extends ComponentTestCaseBase
{
    private const PSR_LOG_OLD_VERSION = '2.0.0';
    private const PSR_LOG_FIRST_NEW_VERSION = '3.0.0';

    private const APP_VENDOR_HAS_OLD_PSR_LOG_KEY = 'app_vendor_has_old_psr_log';
    private const APP_USES_OLD_PSR_LOG_KEY = 'app_uses_old_psr_log';

    private const INSTALLED_DISTRO_PSR_LOG_VERSION_KEY = 'installed_distro_psr_log_version';

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

        $assertLessThanOrEqualTo(true, self::PSR_LOG_OLD_VERSION, self::PSR_LOG_FIRST_NEW_VERSION);
        $assertLessThanOrEqualTo(false, self::PSR_LOG_FIRST_NEW_VERSION, self::PSR_LOG_OLD_VERSION);
    }

    public static function appCodeForTest0DistroHasNewPsrLog(MixedMap $appCodeRequestArgs): void
    {
        AppCodeAuxOutputUtil::writeDataToTempFile([self::INSTALLED_DISTRO_PSR_LOG_VERSION_KEY => InstalledVersions::getVersion('psr/log')], $appCodeRequestArgs);
    }

    private function implTest0DistroHasNewPsrLog(): void
    {
        self::implTestForAppCodeSetsHowFinished(
            testArgs: new MixedMap([]),
            subAppCode: [__CLASS__, 'appCodeForTest0DistroHasNewPsrLog'],
            additionalAssertCode: function (DebugContextScopeRef $dbgCtx, AgentBackendComms $agentBackendComms, MixedMap $appCodeAuxOutput): void {
                $installedDistroPsrLogVersion = $appCodeAuxOutput->getString(self::INSTALLED_DISTRO_PSR_LOG_VERSION_KEY);
                $dbgCtx->add(compact('installedDistroPsrLogVersion'));

                self::assertTrue(ComposerSemverComparator::lessThanOrEqualTo(self::PSR_LOG_FIRST_NEW_VERSION, $installedDistroPsrLogVersion));
            }
        );
    }

    public function test0DistroHasNewPsrLog(): void
    {
        self::runAndEscalateLogLevelOnFailure(self::buildDbgDescForTest(__CLASS__, __FUNCTION__), fn() => $this->implTest0DistroHasNewPsrLog());
    }

    /**
     * @return iterable<string, array{MixedMap}>
     */
    public static function dataProviderForTestOnAppWithDepConflict(): iterable
    {
        if (self::isMainAppCodeHostHttp()) {
            return ['dummy data set' => [new MixedMap()]];
        }

        return self::adaptDataProviderForTestBuilderToSmokeToDescToMixedMap(
            (new DataProviderForTestBuilder())
                ->addProdBoolConfigOptionKeyedDimensionAllValuesCombinable(OptionForProdName::enabled->name)
                ->addProdBoolConfigOptionKeyedDimensionAllValuesCombinable(OptionForProdName::debug_scoper_enabled->name)
                ->addKeyedDimensionAllValuesCombinable(self::APP_VENDOR_HAS_OLD_PSR_LOG_KEY, [true, false])
                ->addKeyedDimensionAllValuesCombinable(self::APP_USES_OLD_PSR_LOG_KEY, [true, false])
        );
    }

    private function implTestOnAppWithDepConflict(MixedMap $testArgs): void
    {
    }

    /**
     * @dataProvider dataProviderForTestOnAppWithDepConflict
     */
    public function testOnAppWithDepConflict(MixedMap $testArgs): void
    {
        if (self::skipIfMainAppCodeHostIsNotCliScript()) {
            return;
        }

        $appsCodeScriptToRestore = CliScriptAppCodeHostHandle::getScriptToRun();
        try {
            CliScriptAppCodeHostHandle::setScriptToRun(FileUtil::partsToPath(__DIR__, 'DependenciesScopingTestApp', 'run.php'));
            self::runAndEscalateLogLevelOnFailure(self::buildDbgDescForTestWithArgs(__CLASS__, __FUNCTION__, $testArgs), fn() => $this->implTestOnAppWithDepConflict($testArgs));
        } finally {
            CliScriptAppCodeHostHandle::setScriptToRun($appsCodeScriptToRestore);
        }
    }
}
