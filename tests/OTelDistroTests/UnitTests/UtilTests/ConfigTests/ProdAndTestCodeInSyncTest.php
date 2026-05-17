<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OpenTelemetry\Distro\PhpPartFacade;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\Config\BoolOptionParser;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\Config\ParseException;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\TestCaseBase;

class ProdAndTestCodeInSyncTest extends TestCaseBase
{
    public function testProdAndTestCodeInSyncTest(): void
    {
        AssertEx::sameConstValues(PhpPartFacade::USER_BOOTSTRAP_PHP_FILE_OPT_NAME, OptionForProdName::user_bootstrap_php_file->name);
    }

    public function testBoolOptionParser(): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $boolOptionParser = new BoolOptionParser();
        $dbgCtx->pushSubScope();
        foreach ([[BoolOptionParser::$falseRawValues, false],[BoolOptionParser::$trueRawValues, true]] as [$rawValues, $expectedParsedValue]) {
            $dbgCtx->resetTopSubScope(compact('expectedParsedValue'));
            $dbgCtx->pushSubScope();
            foreach ($rawValues as $rawValue) {
                $dbgCtx->resetTopSubScope(compact('rawValue'));
                self::assertSame($expectedParsedValue, PhpPartFacade::parseBoolValue($rawValue));
                self::assertSame($expectedParsedValue, $boolOptionParser->parse($rawValue));
                self::assertSame($expectedParsedValue, PhpPartFacade::parseBoolValue(strtoupper($rawValue)));
                self::assertSame($expectedParsedValue, $boolOptionParser->parse(strtoupper($rawValue)));
            }
            $dbgCtx->popSubScope();
        }
        $dbgCtx->popSubScope();

        $assertThrowsParseException = function (callable $callable): void {
            $thrown = false;
            try {
                $callable();
            } /** @noinspection PhpUnusedLocalVariableInspection */ catch (ParseException $ex) {
                $thrown = true;
            }
            self::assertTrue($thrown);
        };

        $dbgCtx->pushSubScope();
        foreach (['invalid', 'value', '123', 'o', 't', 'f'] as $invalidRawValue) {
            $dbgCtx->resetTopSubScope(compact('invalidRawValue'));
            self::assertNull(PhpPartFacade::parseBoolValue($invalidRawValue));
            $assertThrowsParseException(fn() => $boolOptionParser->parse($invalidRawValue));
        }
        $dbgCtx->popSubScope();
    }
}
