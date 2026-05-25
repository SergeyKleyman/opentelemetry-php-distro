<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\EnvVarUtil;
use OTelDistroTests\Util\ExceptionUtil;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\NumericUtilForTests;
use OTelDistroTests\Util\OsUtil;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-import-type EnvVars from EnvVarUtil
 *
 * @phpstan-type ProcessListingInfo array{'parent_pid': int, 'command_line': string}
 * @phpstan-type PidToListingInfo array<int, ProcessListingInfo>
 */
final class ProcessUtil
{
    use StaticClassTrait;

    public const COMMAND_LINE_KEY = 'command_line';
    public const PARENT_PID_KEY = 'parent_pid';

    public const PID_PS_COLUMN_NAME = 'PID';
    public const PPID_PS_COLUMN_NAME = 'PPID';
    public const COMMAND_PS_COLUMN_NAME = 'COMMAND';

    public static function doesProcessExist(int $pid): bool
    {
        exec("ps -p $pid", /* out */ $cmdOutput, /* out */ $cmdExitCode);
        return $cmdExitCode === 0;
    }

    public static function waitForProcessToExitUsingPid(string $dbgProcessDesc, int $pid, int $maxWaitTimeInMicroseconds): bool
    {
        return (new PollingCheck(
            $dbgProcessDesc . ' process (PID: ' . $pid . ') exited' /* <- dbgDesc */,
            $maxWaitTimeInMicroseconds,
        ))->run(
            function () use ($pid): bool {
                return !self::doesProcessExist($pid);
            }
        );
    }

    public static function execCommandToTerminateProcess(int $pid, bool $force = false): bool
    {
        $logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('pid', 'force'));
        $logDebug = $logger->logDebug(__FUNCTION__);
        $shellCmd = 'kill ' . ($force ? '-9 ' : '') . $pid;
        $logger->addAllContext(compact('shellCmd'));
        $logDebug?->with(__LINE__, 'About to execute shell command');
        exec($shellCmd, /* ref */ $cmdOutput, /* ref */ $cmdExitCode);
        $logDebug?->with(__LINE__, 'Executed shell command', compact('cmdExitCode', 'cmdOutput'));
        return $cmdExitCode === 0;
    }

    public static function buildStdErrOutFileFullPath(string $dbgProcessName): ?string
    {
        if (AmbientContextForTests::testConfig()->logsDirectory === null) {
            return null;
        }

        return AmbientContextForTests::testConfig()->logsDirectory . DIRECTORY_SEPARATOR . $dbgProcessName . '_stderr_and_stdout.log';
    }

    private static function addStdErrOutRedirect(string $dbgProcessName, string $command): string
    {
        if (($stdErrOutFilePath = self::buildStdErrOutFileFullPath($dbgProcessName)) === null) {
            return $command;
        }

        $commandForBash = "set -e -o pipefail ; $command 2>&1 | tee \"$stdErrOutFilePath\"";
        return "bash -c \"$commandForBash\"";
    }

    /**
     * @phpstan-param EnvVars $envVars
     */
    public static function startBackgroundProcess(string $dbgProcessName, string $command, array $envVars, ?ResourcesCleanerClient $resourcesCleanerClient, bool $isTestScoped): void
    {
        $processHandle = self::procOpenEx(
            dbgProcessName: $dbgProcessName,
            command: self::addStdErrOutRedirect($dbgProcessName, $command) . '&',
            envVars: $envVars,
            isBackground: true,
            resourcesCleanerClient: $resourcesCleanerClient,
            isTestScoped: $isTestScoped
        );

        // Close handle to allow process to exit
        $processHandle->close();
    }

    /**
     * @phpstan-param EnvVars $envVars
     */
    public static function startProcessAndWaitForItToExit(
        string $dbgProcessName,
        string $command,
        array $envVars,
        ResourcesCleanerClient $resourcesCleanerClient,
        bool $isTestScoped,
        int $maxWaitTimeInMicroseconds,
        ?LogLevel $logLevelTimedout = null,
    ): ProcessInfo {
        $logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__);
        $logger->addAllContext(compact('dbgProcessName', 'command', 'envVars'));

        $processHandle = self::procOpenEx(
            dbgProcessName: $dbgProcessName,
            command: self::addStdErrOutRedirect($dbgProcessName, $command),
            envVars: $envVars,
            isBackground: false,
            resourcesCleanerClient: $resourcesCleanerClient,
            isTestScoped: $isTestScoped
        );
        $logger->addAllContext(compact('processHandle'));

        try {
            $processHandle->waitForProcessToExit($maxWaitTimeInMicroseconds, $logLevelTimedout);
            if (!$processHandle->getCurrentInfo()->hasExited()) {
                $logger->logWithLevel(__FUNCTION__, $logLevelTimedout ?? LogLevel::warning)?->with(__LINE__, 'Wait for the started process to exit timed out - terminating the process');
                self::execCommandToTerminateProcess(AssertEx::isInt($processHandle->getCurrentInfo()->pid));
            }
        } finally {
            $processHandle->close();
        }

        return $processHandle->getCurrentInfo();
    }

    /**
     * @phpstan-param EnvVars $envVars
     */
    private static function procOpenEx(string $dbgProcessName, string $command, array $envVars, bool $isBackground, ?ResourcesCleanerClient $resourcesCleanerClient, bool $isTestScoped): ProcessHandle
    {
        $logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__);
        $logger->addAllContext(compact('dbgProcessName', 'command', 'envVars', 'isBackground'));

        $logDebug = $logger->logDebug(__FUNCTION__);
        $logDebug?->with(__LINE__, "Starting process $dbgProcessName ($command) ...");

        $pipes = [];
        $procOpenRetVal = proc_open($command, /* descriptor_spec: */ [], /* ref */ $pipes, /* cwd: */ null, $envVars);
        $logger->addAllContext(compact('procOpenRetVal'));
        if ($procOpenRetVal === false) {
            $logger->logError(__FUNCTION__)?->with(__LINE__, 'Failed to start process');
            throw new ComponentTestsInfraException(ExceptionUtil::buildMessage('Failed to start process', $logger->getContext()));
        }

        $processHandle = new ProcessHandle($dbgProcessName, $procOpenRetVal);
        $resourcesCleanerClient?->registerProcessToTerminate($dbgProcessName, $processHandle->getCurrentInfo()->pid, $isTestScoped);

        $logInfo = $logger->logInfo(__FUNCTION__);
        $logInfo?->with(__LINE__, "Started process $dbgProcessName ($command)", compact('processHandle'));
        return $processHandle;
    }

    public static function getCurrentPid(): int
    {
        return AssertEx::isInt(getmypid());
    }

    /**
     * @return list<string>
     */
    private static function splitStringOnWhitespace(string $outputLine, int $partsCountLimit): array
    {
        // Use \s+ to match one or more whitespace characters
        return AssertEx::notFalse(preg_split('/\s+/', $outputLine, /* limit: */ $partsCountLimit, /* flags: */ PREG_SPLIT_NO_EMPTY));
    }

    /**
     * @param iterable<string> $outputLines
     *
     * @return PidToListingInfo
     */
    public static function parsePsCommandListingOutput(iterable $outputLines): array
    {
        /**
         * @see ProcessUtilTest::testParsePsCommandListingOutput
         */

        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        /** @var list<string> $expectedFirstLineParts */
        static $expectedFirstLineParts = [self::PID_PS_COLUMN_NAME, self::PPID_PS_COLUMN_NAME, self::COMMAND_PS_COLUMN_NAME];
        /** @var ?int $expectedLinePartsCount */
        static $expectedLinePartsCount = null;
        if ($expectedLinePartsCount === null) {
            $expectedLinePartsCount = count($expectedFirstLineParts);
        }

        Assert::assertTrue(IterableUtil::getFirstValue($outputLines, /* out */ $firstLine));
        /** @var string $firstLine */
        $firstLineParts = self::splitStringOnWhitespace($firstLine, $expectedLinePartsCount);
        $dbgCtx->add(compact('firstLineParts'));

        AssertEx::equalLists($expectedFirstLineParts, $firstLineParts);

        /** @var PidToListingInfo $result */
        $result = [];
        foreach (IterableUtil::skipFirst($outputLines) as $outputLine) {
            $currentLineParts = self::splitStringOnWhitespace($outputLine, $expectedLinePartsCount);
            Assert::assertCount($expectedLinePartsCount, $currentLineParts);
            $pid = NumericUtilForTests::parseStringAsInt($currentLineParts[0]);
            $parentPid = NumericUtilForTests::parseStringAsInt($currentLineParts[1]);
            $command = $currentLineParts[2];
            ArrayUtilForTests::addAssertingKeyNew($pid, [self::PARENT_PID_KEY => $parentPid, self::COMMAND_LINE_KEY => $command], /* ref */ $result);
        }
        return $result;
    }

    /**
     * @return PidToListingInfo
     */
    public static function getAllProcessesListingInfos(): array
    {
        Assert::assertFalse(OsUtil::isWindows());

        DebugContext::getCurrentScope(/* out */ $dbgCtx);
        $cmd = 'ps -o pid,ppid,args';
        $dbgCtx->add(compact('cmd'));
        $outputLastLine = exec('ps -o pid,ppid,args', /* out */ $outputLinesAsArray, /* out */ $exitCode);
        $dbgCtx->add(compact('exitCode', 'outputLinesAsArray', 'outputLastLine'));
        Assert::assertSame(0, $exitCode);
        Assert::assertIsString($outputLastLine);

        return self::parsePsCommandListingOutput($outputLinesAsArray);
    }
}
