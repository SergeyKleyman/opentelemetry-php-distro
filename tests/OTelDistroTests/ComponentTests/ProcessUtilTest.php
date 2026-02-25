<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use OpenTelemetry\Distro\Util\BoolUtil;
use OTelDistroTests\ComponentTests\Util\ComponentTestCaseBase;
use OTelDistroTests\ComponentTests\Util\ConfigUtilForTests;
use OTelDistroTests\ComponentTests\Util\DbgProcessNameGenerator;
use OTelDistroTests\ComponentTests\Util\EnvVarUtilForTests;
use OTelDistroTests\ComponentTests\Util\HelperSleepsAndExitsWithArgCode;
use OTelDistroTests\ComponentTests\Util\InfraUtilForTests;
use OTelDistroTests\ComponentTests\Util\ProcessUtil;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\DataProviderForTestBuilder;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\MixedMap;

/**
 * @group smoke
 * @group does_not_require_external_services
 */
final class ProcessUtilTest extends ComponentTestCaseBase
{
    private const EXIT_CODE = 'exit_code';
    private const SHOULD_WAIT_SUCCEED = 'should_wait_succeed';

    /**
     * @return iterable<string, array{MixedMap}>
     */
    public static function dataProviderForTestStartAndWaitReturnsCorrectExitCode(): iterable
    {
        return self::adaptDataProviderForTestBuilderToSmokeToDescToMixedMap(
            (new DataProviderForTestBuilder())
                ->addKeyedDimensionOnlyFirstValueCombinable(self::EXIT_CODE, [123, 231])
                ->addBoolKeyedDimensionOnlyFirstValueCombinable(self::SHOULD_WAIT_SUCCEED)
        );
    }

    /**
     * @dataProvider dataProviderForTestStartAndWaitReturnsCorrectExitCode
     */
    public function testStartAndWaitReturnsCorrectExitCode(MixedMap $testArgs): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);
        $logger = self::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__);
        $loggerProxy = $logger->ifDebugLevelEnabledNoLine(__FUNCTION__);

        $testCaseHandle = $this->getTestCaseHandle();
        $exitCode = $testArgs->getInt(self::EXIT_CODE);
        $shouldWaitSucceed = $testArgs->getBool(self::SHOULD_WAIT_SUCCEED);
        if ($shouldWaitSucceed) {
            $helperToSleepSeconds = 0;
            $waitForHelperToExitSecondsInMicroseconds = 100 * 1000_000;
        } else {
            $helperToSleepSeconds = 1000;
            $waitForHelperToExitSecondsInMicroseconds = 1;
        }

        $dbgProcessName = DbgProcessNameGenerator::generate(ClassNameUtil::fqToShort(HelperSleepsAndExitsWithArgCode::class));
        $runHelperScriptFullPath = FileUtil::partsToPath(__DIR__, 'Util', 'runHelperSleepsAndExitsWithArgCode.php');
        $command = "php \"$runHelperScriptFullPath\" $helperToSleepSeconds $exitCode";
        $baseEnvVars = EnvVarUtilForTests::getAll();
        $additionalEnvVars = [
            OptionForProdName::autoload_enabled->toEnvVarName()          => BoolUtil::toString(false),
            OptionForProdName::disabled_instrumentations->toEnvVarName() => ConfigUtilForTests::PROD_DISABLED_INSTRUMENTATIONS_ALL,
            OptionForProdName::enabled->toEnvVarName()                   => BoolUtil::toString(false),
        ];
        ArrayUtilForTests::append(from: $additionalEnvVars, to: $baseEnvVars);

        $envVars = InfraUtilForTests::buildEnvVarsForSpawnedProcessWithoutAppCode(
            $dbgProcessName,
            InfraUtilForTests::generateSpawnedProcessInternalId(),
            [] /* <- ports */,
            $testCaseHandle->getResourcesCleaner(),
        );

        $loggerProxy && $loggerProxy->log(__LINE__, 'Before ProcessUtil::startProcessAndWaitForItToExit');
        $procInfo = ProcessUtil::startProcessAndWaitForItToExit($dbgProcessName, $command, $envVars, $waitForHelperToExitSecondsInMicroseconds);
        $dbgCtx->add(compact('procInfo'));
        $loggerProxy && $loggerProxy->log(__LINE__, 'After ProcessUtil::startProcessAndWaitForItToExit');
        if ($shouldWaitSucceed) {
            self::assertSame($exitCode, $procInfo['exitCode']);
        } else {
            self::assertNull($procInfo['exitCode']);
        }
    }
}
