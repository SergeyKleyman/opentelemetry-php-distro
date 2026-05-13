<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OpenTelemetry\Distro\BootstrapStageLogLevelUtil;
use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\PhpPartFacade;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\TestCaseBase;
use ReflectionClass;

class ProdAndTestCodeInSyncTest extends TestCaseBase
{
    public function testProdAndTestCodeInSyncTest(): void
    {
        AssertEx::sameConstValues(PhpPartFacade::DEBUG_SCOPER_ENABLED_OPT_NAME, OptionForProdName::debug_scoper_enabled->name);
        AssertEx::sameConstValues(PhpPartFacade::USER_BOOTSTRAP_PHP_FILE_OPT_NAME, OptionForProdName::user_bootstrap_php_file->name);
    }

    public function testBootstrapStageLogLevelUtil(): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $bootstrapStageLoggerReflClass = new ReflectionClass(BootstrapStageLogLevelUtil::class);

        // Verify that number of LEVEL_* consts in BootstrapStageLogger is the same as the number of cases in LogLevel
        $constsNameToVal = array_filter(
            $bootstrapStageLoggerReflClass->getConstants(),
            function (mixed $constVal, string $constName): bool {
                return str_starts_with($constName, 'LEVEL_') && is_int($constVal);
            },
            ARRAY_FILTER_USE_BOTH,
        );
        $dbgCtx->add(compact('constsNameToVal'));
        self::assertCount(count(LogLevel::cases()), $constsNameToVal);
        /** @var array<string, int> $constsNameToVal */

        // Verify each LEVEL_* const in BootstrapStageLogger has the same value as the correspinding case in LogLevel
        $dbgCtx->pushSubScope();
        foreach (LogLevel::cases() as $level) {
            $dbgCtx->resetTopSubScope(compact('level'));
            $constName = 'LEVEL_' . strtoupper($level->name);
            $dbgCtx->add(compact('constName'));
            self::assertTrue($bootstrapStageLoggerReflClass->hasConstant($constName));
            $constVal = $bootstrapStageLoggerReflClass->getConstant($constName);
            self::assertSame($level->value, $constVal);

            self::assertSame(strtoupper($level->name), BootstrapStageLogLevelUtil::levelIntToString($constVal));

            self::assertSame($level->value, BootstrapStageLogLevelUtil::levelStringToInt(strtoupper($level->name)));
            self::assertSame($level->value, BootstrapStageLogLevelUtil::levelStringToInt(strtolower($level->name)));
        }
        $dbgCtx->popSubScope();

        // Verify strings generated for not predefined int values
        $maxPredefinedIntVal = max(AssertEx::notEmptyList(array_values($constsNameToVal)));
        foreach ([1, 12, 321, 4567] as $delta) {
            $notPredefinedLevelIntVal = $maxPredefinedIntVal + $delta;
            self::assertSame('LEVEL ' . $notPredefinedLevelIntVal, BootstrapStageLogLevelUtil::levelIntToString($notPredefinedLevelIntVal));
            self::assertNull(BootstrapStageLogLevelUtil::levelStringToInt(BootstrapStageLogLevelUtil::levelIntToString($notPredefinedLevelIntVal)));
        }
    }
}
