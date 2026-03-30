<?php

declare(strict_types=1);

use OpenTelemetry\Distro\AutoloaderDistroOTelClasses;
use OpenTelemetry\Distro\BootstrapStageLogger;
use OpenTelemetry\Distro\Log\LogLevel;
use RuntimeException;

require __DIR__ . '/bootstrap_shared.php';

// __DIR__ is "<repo root>/tools"
$repoRootDir = realpath($repoRootDirTempVal = __DIR__ . DIRECTORY_SEPARATOR . '..');
if ($repoRootDir === false) {
    throw new RuntimeException("realpath returned false for $repoRootDirTempVal");
}

$prodPhpElasticOTelPath = $repoRootDir . '/prod/php/ElasticOTel';
require $prodPhpElasticOTelPath . '/Util/EnumUtilTrait.php';
require $prodPhpElasticOTelPath . '/Log/LogLevel.php';
require $prodPhpElasticOTelPath . '/BootstrapStageStdErrWriter.php';
require $prodPhpElasticOTelPath . '/BootstrapStageLogger.php';

require __DIR__ . '/ToolsAssertTrait.php';
require __DIR__ . '/ToolsLog.php';
require __DIR__ . '/ToolsLoggingClassTrait.php';

$getMaxEnabledLogLevel = function (string $envVarName, LogLevel $default): LogLevel {
    $envVarVal = getenv($envVarName);
    if (!is_string($envVarVal)) {
        return $default;
    }

    return LogLevel::tryToFindByName(strtolower($envVarVal)) ?? $default;
};

ToolsLog::configure($getMaxEnabledLogLevel('ELASTIC_OTEL_PHP_TOOLS_LOG_LEVEL', default: LogLevel::info));

$prodLogLevel = $getMaxEnabledLogLevel('ELASTIC_OTEL_LOG_LEVEL_STDERR', default: LogLevel::info);

$writeToSinkForBootstrapStageLogger = function (int $level, int $feature, string $file, int $line, string $func, string $text): void {
    ToolsLog::writeAsProdSink($level, $feature, $file, $line, $func, $text);
};
BootstrapStageLogger::configure($prodLogLevel->value, $prodPhpElasticOTelPath, $writeToSinkForBootstrapStageLogger);

require $prodPhpElasticOTelPath . '/AutoloaderElasticOTelClasses.php';
AutoloaderDistroOTelClasses::register('Elastic\\OTel', $prodPhpElasticOTelPath);
AutoloaderDistroOTelClasses::register(__NAMESPACE__, __DIR__);
