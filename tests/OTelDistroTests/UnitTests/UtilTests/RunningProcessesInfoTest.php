<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use Ds\Set;
use OTelDistroTests\ComponentTests\Util\RunningProcessAdditionalDetails;
use OTelDistroTests\ComponentTests\Util\RunningProcessesInfo;
use OTelDistroTests\ComponentTests\Util\ProcessUtil;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\OsUtil;
use OTelDistroTests\Util\TestCaseBase;
use OTelDistroTests\Util\TextUtilForTests;
use PHPUnit\Exception as PHPUnitExceptionInterface;

/**
 * @phpstan-import-type Pid from RunningProcessesInfo
 * @phpstan-import-type PidToAdditionalDetails from RunningProcessesInfo
 */
class RunningProcessesInfoTest extends TestCaseBase
{
    /**
     * @phpstan-param Pid $parentPid
     */
    private static function newAdditionalDetails(int $parentPid, string $state, string $commandLine): RunningProcessAdditionalDetails
    {
        return new RunningProcessAdditionalDetails(parentPid: $parentPid, state: $state, commandLine: $commandLine);
    }

    public function testParsePsCommandListingOutput(): void
    {
        /**
         * @param iterable<string> $outputLines
         * @phpstan-param PidToAdditionalDetails $expectedResult
         */
        $impl = function (iterable $outputLines, array $expectedResult): void {
            /** @var iterable<string> $outputLines */
            /** @var PidToAdditionalDetails $expectedResult */

            DebugContext::getCurrentScope(/* out */ $dbgCtx);

            $actualResult = RunningProcessesInfo::parsePsCommandOutput($outputLines);
            AssertEx::equal(new RunningProcessesInfo($expectedResult), $actualResult);
        };

        // No output lines should cause an exception to be thrown
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => $impl([], []));

        // Output lines has only just the header
        $impl([" \t PID    PPID STAT COMMAND"], []);

        $impl(
            [
                'PID    PPID STAT COMMAND',
                '209280    1 S+   php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log',
            ],
            [
                209280 => self::newAdditionalDetails(parentPid: 1, state: 'S+', commandLine: 'php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log'),
            ],
        );

        $impl(
            [
                'PID    PPID    STAT COMMAND',
                "209277  209253 S+   sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'",
                '209280       1 SI+  php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log',
            ],
            [
                209277 => self::newAdditionalDetails(parentPid: 209253, state: 'S+', commandLine: "sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'"),
                209280 => self::newAdditionalDetails(parentPid: 1, state: 'SI+', commandLine: 'php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log'),
            ],
        );

        /** @phpstan-var string $exampleOutputAsOneString */
        static $exampleOutputAsOneString = <<<'END_OF_STRING_MARKER_4d416bb9_c85c_4df2_9d83_a2d144e79cdc'
                    PID    PPID STAT COMMAND
                 209277  209253 RI   sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'
                 209278  209277 SI+  php phpunit -c phpunit_component_tests.xml --group does_not_require_external_services
                 209280       1 R+   php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log
            END_OF_STRING_MARKER_4d416bb9_c85c_4df2_9d83_a2d144e79cdc;

        $exampleOutputResult = [
            209277 => self::newAdditionalDetails(parentPid: 209253, state: 'RI', commandLine: "sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'"),
            209278 => self::newAdditionalDetails(parentPid: 209277, state: 'SI+', commandLine: "php phpunit -c phpunit_component_tests.xml --group does_not_require_external_services"),
            209280 => self::newAdditionalDetails(parentPid: 1, state: 'R+', commandLine: "php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log"),
        ];

        $impl(TextUtilForTests::iterateLines($exampleOutputAsOneString), $exampleOutputResult);
    }

    private function getProcessCommandLine(int $pid): string
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $procCmdLineFileContents = FileUtil::getFileContents("/proc/$pid/cmdline");
        // Arguments in /proc/pid/cmdline are null-separated (\0)
        return trim(str_replace("\0", ' ', $procCmdLineFileContents));
    }

    public function testGetProcessSubTreeAdditionalDetails(): void
    {
        if (OsUtil::isWindows()) {
            self::dummyAssert();
            return;
        }

        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $myPid = ProcessUtil::getCurrentPid();
        $dbgCtx->add(compact('myPid'));
        self::assertSame(posix_getpid(), $myPid);
        $parentPid = posix_getppid();
        $dbgCtx->add(compact('parentPid'));
        $actualRunningProcesses = RunningProcessesInfo::getForAllInCurrentSession();
        $dbgCtx->add(compact('actualRunningProcesses'));
        $actualMyAdditionalDetails = $actualRunningProcesses[$myPid];
        $dbgCtx->add(compact('actualMyAdditionalDetails'));
        self::assertSame($parentPid, $actualMyAdditionalDetails->parentPid);
        global $argv;
        $expectedMyCommandLineSuffix = implode(' ', $argv);
        $dbgCtx->add(compact('expectedMyCommandLineSuffix'));
        $expectedMyCommandLine = self::getProcessCommandLine($myPid);
        $dbgCtx->add(compact('expectedMyCommandLine'));
        self::assertStringEndsWith($expectedMyCommandLineSuffix, $expectedMyCommandLine);
        self::assertSame($expectedMyCommandLine, $actualMyAdditionalDetails->commandLine);

        $expectedParentCommandLine = self::getProcessCommandLine($parentPid);
        $actualParentAdditionalDetails = AssertEx::arrayHasKey($parentPid, $actualRunningProcesses);
        $dbgCtx->add(compact('actualParentAdditionalDetails'));
        self::assertSame($expectedParentCommandLine, $actualParentAdditionalDetails->commandLine);

        self::assertTrue($actualRunningProcesses->isDescendantOf($myPid, $parentPid));
    }

    /**
     * @param array<Pid, Pid> $pidToParentPid
     *
     * @return RunningProcessesInfo
     */
    private static function buildFromPidToParentPid(array $pidToParentPid): RunningProcessesInfo
    {
        return new RunningProcessesInfo(array_map(fn($parentPid) => self::newAdditionalDetails($parentPid, 'dummy state', 'dummy cmd'), $pidToParentPid));
    }

    public function testIterateAncestorsOf(): void
    {
        /**
         * @param array<Pid, Pid> $pidToParentPid
         * @phpstan-param Pid $pid
         * @phpstan-param list<Pid> $expectedResult
         */
        $impl = function (array $pidToParentPid, int $pid, array $expectedResult): void {
            /** @var array<Pid, Pid> $pidToParentPid */
            /** @var Pid $pid */
            /** @var list<Pid> $expectedResult */

            $processesInfos = self::buildFromPidToParentPid($pidToParentPid);
            $actualResult = IterableUtil::toList($processesInfos->iterateAncestorsOf($pid));
            AssertEx::equal($expectedResult, $actualResult);
        };

        $impl([], 1, []);
        $impl([111 => 11], 1, []);
        $impl([111 => 11], 11, []);
        $impl([111 => 11], 111, [11]);
        $impl([111 => 11], 11, []);
        $impl([111 => 11, 11 => 1], 111, [11, 1]);
        $impl([111 => 11, 11 => 1], 11, [1]);
        $impl([111 => 11, 11 => 1], 1, []);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 111, [11, 1]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 11, [1]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 12, [1]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 111, [11, 1]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 121, [12, 1]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], 122, [12, 1]);
    }

    public function testGetSubTrees(): void
    {
        /**
         * @param array<Pid, Pid> $basePidToParentPid
         * @param list<Pid> $rootPids
         * @param array<Pid, Pid> $expectedPidsInResult
         */
        $impl = function (array $basePidToParentPid, array $rootPids, array $expectedPidsInResult): void {
            /** @var array<Pid, Pid> $basePidToParentPid */
            /** @var list<Pid> $rootPids */
            /** @var list<Pid> $expectedPidsInResult */

            $baseProcessesInfos = self::buildFromPidToParentPid($basePidToParentPid);
            $actualResult = $baseProcessesInfos->getSubTrees(new Set($rootPids));
            AssertEx::equalAsSets(IterableUtil::toList(IterableUtil::keys($expectedPidsInResult)), IterableUtil::toList(IterableUtil::keys($actualResult)));
            foreach ($actualResult as $pid => $actualAdditionalDetails) {
                AssertEx::equal($baseProcessesInfos[$pid], $actualAdditionalDetails);
            }
        };

        $impl([], [], []);
        $impl([11 => 1], [1], [11 => 1]);
        $impl([11 => 1], [11], [11 => 1]);
        $impl([111 => 11, 11 => 1], [111], [111 => 11]);
        $impl([111 => 11, 11 => 1], [11], [111 => 11, 11 => 1]);
        $impl([111 => 11, 11 => 1], [111, 1], [111 => 11, 11 => 1]);
        $impl([111 => 11, 11 => 1], [111, 11], [111 => 11, 11 => 1]);
        $impl([111 => 11, 11 => 1], [111, 10], [111 => 11]);
        $impl([111 => 11, 11 => 1], [10, 111], [111 => 11]);

        $impl([122 => 12, 121 => 12, 111 => 11, 22 => 2, 21 => 2, 12 => 1, 11 => 1, 2 => 0, 1 => 0], [], [11, 1]);
    }

    public function testIterateInTopologicalOrder(): void
    {
        /**
         * @param array<Pid, Pid> $pidToParentPid
         * @phpstan-param Pid $pid
         * @phpstan-param list<Pid> $expectedResult
         */
        $impl = function (array $pidToParentPid, array $expectedResult): void {
            /** @var array<Pid, Pid> $pidToParentPid */
            /** @var list<Pid> $expectedResult */

            $processesInfos = self::buildFromPidToParentPid($pidToParentPid);
            $actualResult = IterableUtil::toList(IterableUtil::keys($processesInfos->iterateInTopologicalOrder()));
            AssertEx::equal($expectedResult, $actualResult);
        };

        $impl([], []);
        $impl([11 => 1], [11]);
        $impl([122 => 12, 121 => 12, 111 => 11, 12 => 1, 11 => 1], [122, 121, 111, 12, 11]);
    }
}
