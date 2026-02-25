<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\Log\LogExternalClassesRegistry;
use PHPUnit\Event\Code\Test as PHPUnitEventCodeTest;
use PHPUnit\Event\Code\TestMethod as PHPUnitEventCodeTestMethod;
use PHPUnit\Event\Event as PHPUnitEvent;
use PHPUnit\Event\Telemetry\Info as PHPUnitTelemetryInfo;
use PHPUnit\Event\Telemetry\HRTime as PHPUnitTelemetryHRTime;

/**
 * @phpstan-import-type ConverterToLog from LogExternalClassesRegistry
 */
final class PHPUnitToLogConverters
{
    use StaticClassTrait;

    public static function register(): void
    {
        LogExternalClassesRegistry::singletonInstance()->addFinder(self::findConverter(...));
    }

    /**
     * @return ?ConverterToLog
     */
    public static function findConverter(object $object): ?callable
    {
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $result = match (true) {
            $object instanceof PHPUnitEvent => self::convertPHPUnitEvent(...),
            $object instanceof PHPUnitEventCodeTestMethod => self::convertPHPUnitEventCodeTestMethod(...),
            $object instanceof PHPUnitEventCodeTest => self::convertPHPUnitEventCodeTest(...),
            $object instanceof PHPUnitTelemetryHRTime => self::convertPHPUnitTelemetryHRTime(...),
            $object instanceof PHPUnitTelemetryInfo => self::convertPHPUnitTelemetryInfo(...),
            default => null
        };

        /** @var ?ConverterToLog $result */
        return $result; // @phpstan-ignore varTag.nativeType
    }

    /**
     * @return array<string, mixed>
     */
    private static function convertPHPUnitEvent(PHPUnitEvent $object): array
    {
        return ['asString' => $object->asString(), 'telemetryInfo' => $object->telemetryInfo()];
    }

    /**
     * @return array<string, mixed>
     */
    private static function convertPHPUnitEventCodeTestMethod(PHPUnitEventCodeTestMethod $object): array
    {
        $result = ['class::method' => ($object->className() . '::' . $object->methodName())];
        if ($object->testData()->hasDataFromDataProvider()) {
            $dataSetName = $object->testData()->dataFromDataProvider()->dataSetName();
            $dataSetDesc = is_int($dataSetName) ? "#$dataSetName" : $dataSetName;
            $result['data set'] = $dataSetDesc;
        }
        return $result;
    }

    private static function convertPHPUnitEventCodeTest(PHPUnitEventCodeTest $object): string
    {
        return $object->id();
    }

    /**
     * @return array<string, mixed>
     */
    private static function convertPHPUnitTelemetryHRTime(PHPUnitTelemetryHRTime $object): array
    {
        return ['seconds' => $object->seconds(), 'nanoseconds' => $object->nanoseconds()];
    }

    /**
     * @return array<string, mixed>
     */
    private static function convertPHPUnitTelemetryInfo(PHPUnitTelemetryInfo $object): array
    {
        return [
            'time' => $object->time(),
            'durationSincePrevious' => $object->durationSincePrevious()->asString(),
            'durationSinceStart' => $object->durationSinceStart()->asString(),
            'memoryUsage (bytes)' => $object->memoryUsage()->bytes(),
            'memoryUsageSincePrevious (bytes)' => $object->memoryUsageSincePrevious()->bytes(),
        ];
    }
}
