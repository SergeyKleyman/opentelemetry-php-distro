<?php

/** @noinspection PhpUnitMisorderedAssertEqualsArgumentsInspection */

declare(strict_types=1);

namespace OTelDistroTests\UnitTests;

use OpenTelemetry\Distro\RemoteConfigHandler;
use OTelDistroTests\Util\TestCaseBase;
use OpenTelemetry\API\Behavior\Internal\Logging as OTelInternalLogging;
use OpenTelemetry\SDK\Common\Configuration\Variables as OTelSdkConfigVariables;
use OpenTelemetry\SDK\Sdk as OTelSdk;
use ReflectionClass;

final class DistroDependenciesOnOTelSdkTest extends TestCaseBase
{
    /**
     * @param class-string<object> $classFqName
     *
     * @noinspection PhpDocMissingThrowsInspection
     */
    private static function getPrivateConstValue(string $classFqName, string $constName): mixed
    {
        $reflClass = new ReflectionClass($classFqName);
        self::assertTrue($reflClass->hasConstant($constName));
        return $reflClass->getConstant($constName);
    }

    public function testLogLevelRelatedNames(): void
    {
        self::assertSame(self::getPrivateConstValue(OTelInternalLogging::class, 'OTEL_LOG_LEVEL'), OTelSdkConfigVariables::OTEL_LOG_LEVEL);
        self::assertSame(self::getPrivateConstValue(OTelInternalLogging::class, 'NONE'), RemoteConfigHandler::OTEL_LOG_LEVEL_NONE);
    }

    public function testDeactivateAllInstrumentationsRelatedNames(): void
    {
        self::assertSame(self::getPrivateConstValue(OTelSdk::class, 'OTEL_PHP_DISABLED_INSTRUMENTATIONS_ALL'), RemoteConfigHandler::OTEL_PHP_DISABLED_INSTRUMENTATIONS_ALL);
    }
}
