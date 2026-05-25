<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\ComponentTests\Util\ProcessUtil;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\OsUtil;
use OTelDistroTests\Util\TestCaseBase;
use OTelDistroTests\Util\TextUtilForTests;
use PHPUnit\Exception as PHPUnitExceptionInterface;

/**
 * @phpstan-import-type PidToListingInfo from ProcessUtil
 */
class ProcessUtilTest extends TestCaseBase
{
    public function testParsePsCommandListingOutput(): void
    {
        /**
         * @param iterable<string> $outputLines
         * @phpstan-param PidToListingInfo $expectedResult
         */
        $impl = function (iterable $outputLines, array $expectedResult): void {
            /** @var iterable<string> $outputLines */
            /** @var PidToListingInfo $expectedResult */
            $actualResult = ProcessUtil::parsePsCommandListingOutput($outputLines);
            AssertEx::equalScalarLists(array_keys($expectedResult), array_keys($actualResult));
            foreach ($expectedResult as $pid => $expectedListingInfo) {
                AssertEx::equalMaps($expectedListingInfo, $actualResult[$pid]);
            }
        };

        // No output lines should cause an exception to be thrown
        AssertEx::throws(PHPUnitExceptionInterface::class, fn() => $impl([], []));

        // Output lines has only just the header
        $impl([" \t PID    PPID COMMAND"], []);

        $impl(
            [
                'PID    PPID COMMAND',
                '209280       1 php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log',
            ],
            [
                209280 => [ProcessUtil::PARENT_PID_KEY => 1, ProcessUtil::COMMAND_LINE_KEY => 'php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log'],
            ],
        );

        $impl(
            [
                'PID    PPID COMMAND',
                "209277  209253 sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'",
                '209280       1 php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log',
            ],
            [
                209277 => [ProcessUtil::PARENT_PID_KEY => 209253, ProcessUtil::COMMAND_LINE_KEY => "sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'"],
                209280 => [ProcessUtil::PARENT_PID_KEY => 1, ProcessUtil::COMMAND_LINE_KEY => 'php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log'],
            ],
        );

        /** @phpstan-var string $exampleOutputAsOneString */
        static $exampleOutputAsOneString = <<<'END_OF_STRING_MARKER_4d416bb9_c85c_4df2_9d83_a2d144e79cdc'
                    PID    PPID COMMAND
                 209277  209253 sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'
                 209278  209277 php phpunit -c phpunit_component_tests.xml --group does_not_require_external_services
                 209280       1 php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log
            END_OF_STRING_MARKER_4d416bb9_c85c_4df2_9d83_a2d144e79cdc;

        $exampleOutputResult = [
            209277 => [ProcessUtil::PARENT_PID_KEY => 209253, ProcessUtil::COMMAND_LINE_KEY => "sh -c phpunit -c phpunit_component_tests.xml '--group' 'does_not_require_external_services'"],
            209278 => [ProcessUtil::PARENT_PID_KEY => 209277, ProcessUtil::COMMAND_LINE_KEY => "php phpunit -c phpunit_component_tests.xml --group does_not_require_external_services"],
            209280 => [ProcessUtil::PARENT_PID_KEY => 1, ProcessUtil::COMMAND_LINE_KEY => "php runResourcesCleaner.php 2>&1 | tee ResourcesCleaner.log"],
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

    public function testGetProcessSubTreeListingInfo(): void
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
        $actualPidToListingInfo = ProcessUtil::getAllProcessesListingInfos();
        $dbgCtx->add(compact('actualPidToListingInfo'));
        $actualMyListingInfo = AssertEx::arrayHasKey($myPid, $actualPidToListingInfo);
        $dbgCtx->add(compact('actualMyListingInfo'));
        self::assertSame($parentPid, AssertEx::arrayHasKey(ProcessUtil::PARENT_PID_KEY, $actualMyListingInfo));
        global $argv;
        $expectedMyCommandLineSuffix = implode(' ', $argv);
        $dbgCtx->add(compact('expectedMyCommandLineSuffix'));
        $expectedMyCommandLine = self::getProcessCommandLine($myPid);
        $dbgCtx->add(compact('expectedMyCommandLine'));
        self::assertStringEndsWith($expectedMyCommandLineSuffix, $expectedMyCommandLine);
        $actualMyCommandLine = AssertEx::arrayHasKey(ProcessUtil::COMMAND_LINE_KEY, $actualMyListingInfo);
        $dbgCtx->add(compact('actualMyCommandLine'));
        self::assertSame($expectedMyCommandLine, $actualMyCommandLine);

        $expectedParentCommandLine = self::getProcessCommandLine($parentPid);
        /** @var PidToListingInfo $actualPidToListingInfo */
        $actualParentListingInfo = AssertEx::arrayHasKey($parentPid, $actualPidToListingInfo);
        $dbgCtx->add(compact('actualParentListingInfo'));
        $actualParentCommandLine = AssertEx::arrayHasKey(ProcessUtil::COMMAND_LINE_KEY, $actualParentListingInfo);
        $dbgCtx->add(compact('actualParentCommandLine'));
        self::assertSame($expectedParentCommandLine, $actualParentCommandLine);
    }
}
