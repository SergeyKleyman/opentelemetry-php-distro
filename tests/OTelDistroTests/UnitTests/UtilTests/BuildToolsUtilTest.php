<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OpenTelemetry\Distro\Log\LogFeature;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\TestCaseBase;
use OTelDistroTools\Build\BuildToolsLog;
use ReflectionClass;

final class BuildToolsUtilTest extends TestCaseBase
{
    public static function testProdLogFeatureValueToNameMap(): void
    {
        $logFeatureValueToNameMap = AssertEx::notEmptyArray(BuildToolsLog::buildProdLogFeatureValueToNameMap());

        $assertValueToName = function (int $value, string $expectedName) use ($logFeatureValueToNameMap): void {
            self::assertSame($expectedName, AssertEx::arrayHasKey($value, $logFeatureValueToNameMap));
        };

        $assertValueToName(LogFeature::ALL, 'ALL');
        $assertValueToName(LogFeature::CONFIG, 'CONFIG');

        $logFeatureConstNames = array_keys((new ReflectionClass(LogFeature::class))->getConstants());
        foreach ($logFeatureConstNames as $logFeatureConstName) {
            $assertValueToName(AssertEx::isInt(constant(LogFeature::class . '::' . $logFeatureConstName)), $logFeatureConstName);
        }

        self::assertArrayNotHasKey('dummy name', $logFeatureValueToNameMap);
    }
}
