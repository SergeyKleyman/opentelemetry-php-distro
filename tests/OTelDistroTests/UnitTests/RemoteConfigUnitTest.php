<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests;

use OpenTelemetry\Distro\RemoteConfigHandler;
use OTelDistroTests\Util\TestCaseBase;

final class RemoteConfigUnitTest extends TestCaseBase
{
    public function testMergeDisabledInstrumentations(): void
    {
        $impl = function (string $localVal, string $remoteVal, string $expectedMergedVal): void {
            $actualMergedVal = RemoteConfigHandler::mergeDisabledInstrumentations($localVal, $remoteVal);
            self::assertSame($expectedMergedVal, $actualMergedVal);
        };

        $impl('', '', '');
        $impl("\t", " \n ", '');
        $impl('a', '', 'a');
        $impl('', 'b', 'b');
        $impl('a', 'b', 'a,b');
        $impl('1,b', 'c,4', '1,b,c,4');
        $impl("1\n, b", "\t c, 4", '1,b,c,4');
    }
}
