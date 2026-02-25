<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\Util\JsonUtil;
use OTelDistroTests\Util\Log\LoggableToString;
use OTelDistroTests\Util\PHPUnitExtensionBase;
use OTelDistroTests\Util\TestCaseBase;

class PHPUnitToLogConvertersTest extends TestCaseBase
{
    public function testPHPUnitEvent(): void
    {
        $eventObj = PHPUnitExtensionBase::$lastBeforeTestCaseEvent;
        self::assertNotNull($eventObj);
        $converted = LoggableToString::convert($eventObj);
        self::assertStringContainsString(JsonUtil::adaptStringToSearchInJson(__CLASS__), $converted);
        self::assertStringContainsString(__FUNCTION__, $converted);
    }

    public function testPHPUnitEventCodeTest(): void
    {
        $eventObj = PHPUnitExtensionBase::$lastBeforeTestCaseEvent;
        self::assertNotNull($eventObj);
        $testObj = $eventObj->test();
        $converted = LoggableToString::convert($testObj);
        self::assertStringContainsString(JsonUtil::adaptStringToSearchInJson(__CLASS__), $converted);
        self::assertStringContainsString(__FUNCTION__, $converted);
    }

    public function testPHPUnitTelemetryInfo(): void
    {
        $eventObj = PHPUnitExtensionBase::$lastBeforeTestCaseEvent;
        self::assertNotNull($eventObj);
        $telemetryInfo = $eventObj->telemetryInfo();
        $converted = LoggableToString::convert($telemetryInfo);
        self::assertStringContainsString('time', $converted);
        self::assertStringContainsString('duration', $converted);
        self::assertStringContainsString('memory', $converted);
        self::assertStringContainsString(strval($telemetryInfo->memoryUsage()->bytes()), $converted);
    }

    public function testPHPUnitTelemetryHRTime(): void
    {
        $eventObj = PHPUnitExtensionBase::$lastBeforeTestCaseEvent;
        self::assertNotNull($eventObj);
        $hrTime = $eventObj->telemetryInfo()->time();
        $converted = LoggableToString::convert($hrTime);
        self::assertStringContainsString(strval($hrTime->seconds()), $converted);
        self::assertStringContainsString(strval($hrTime->nanoseconds()), $converted);
    }
}
