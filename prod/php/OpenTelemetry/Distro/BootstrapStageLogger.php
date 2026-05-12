<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

use Closure;
use OpenTelemetry\Distro\Log\LogFeature;

/**
 * @phpstan-type FormatAndWrite Closure(int $level, int $prodLogFeature, string $file, int $line, string $func, string $message): void
 */
final class BootstrapStageLogger
{
    private static int $maxEnabledLevel = BootstrapStageLogLevelUtil::LEVEL_OFF;

    /** @var ?FormatAndWrite */
    private static ?Closure $formatAndWrite = null;

    private static string $phpSrcCodePathPrefixToRemove;
    private static string $classNamePrefixToRemove;

    private static ?int $pid = null;

    /**
     * @phpstan-param ?FormatAndWrite $formatAndWrite
     */
    public static function configure(int $maxEnabledLevel, string $phpSrcCodeRootDir, string $rootNamespace, ?Closure $formatAndWrite = null): void
    {
        require __DIR__ . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . 'LogFeature.php';

        self::$maxEnabledLevel = $maxEnabledLevel;
        self::$formatAndWrite = $formatAndWrite;
        if (is_int($pid = getmypid())) {
            self::$pid = $pid;
        }

        self::$phpSrcCodePathPrefixToRemove = $phpSrcCodeRootDir . DIRECTORY_SEPARATOR;
        self::$classNamePrefixToRemove = $rootNamespace . '\\';

        self::logWithLevel(
            LogFeature::BOOTSTRAP,
            BootstrapStageLogLevelUtil::LEVEL_DEBUG,
            'Exiting...'
            . '; maxEnabledLevel: ' . BootstrapStageLogLevelUtil::levelIntToString($maxEnabledLevel)
            . '; phpSrcCodePathPrefixToRemove: ' . self::$phpSrcCodePathPrefixToRemove
            . '; classNamePrefixToRemove: ' . self::$classNamePrefixToRemove
            . '; pid: ' . self::nullableToLog(self::$pid),
            __FILE__,
            __LINE__,
            __CLASS__,
            __FUNCTION__
        );
    }

    public static function nullableToLog(null|int|string $str): string
    {
        return $str === null ? 'null' : strval($str);
    }

    public static function isEnabledForLevel(int $statementLevel): bool
    {
        return $statementLevel <= self::$maxEnabledLevel;
    }

    private static function isPrefixOf(string $prefix, string $text, bool $isCaseSensitive = true): bool
    {
        $prefixLen = strlen($prefix);
        if ($prefixLen === 0) {
            return true;
        }

        return substr_compare(
            $text /* <- haystack */,
            $prefix /* <- needle */,
            0 /* <- offset */,
            $prefixLen /* <- length */,
            !$isCaseSensitive /* <- case_insensitivity */
        ) === 0;
    }

    private static function processSourceCodeFilePathForLog(string $file): string
    {
        return
            self::isPrefixOf(self::$phpSrcCodePathPrefixToRemove, $file, /* isCaseSensitive: */ false)
                ? substr($file, strlen(self::$phpSrcCodePathPrefixToRemove))
                : $file;
    }

    private static function processClassNameForLog(string $class): string
    {
        return
            self::isPrefixOf(self::$classNamePrefixToRemove, $class, /* isCaseSensitive: */ false)
                ? substr($class, strlen(self::$classNamePrefixToRemove))
                : $class;
    }

    private static function processClassFunctionNameForLog(string $class, string $func): string
    {
        if ($class === '') {
            return $func;
        }
        return self::processClassNameForLog($class) . '::' . $func;
    }

    public static function logWithLevel(int $logFeature, int $statementLevel, string $message, string $file, int $line, string $class, string $func): void
    {
        self::logWithFeatureAndLevel($logFeature, $statementLevel, $message, $file, $line, $class, $func);
    }

    public static function logWithFeatureAndLevel(int $prodLogFeature, int $statementLevel, string $message, string $file, int $line, string $class, string $func): void
    {
        if (!self::isEnabledForLevel($statementLevel)) {
            return;
        }

        if (self::$formatAndWrite === null) {
            /**
             * Use fully qualified names for functions implemented by the extension to make sure scoper correctly detects them
             * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
             */
            \OpenTelemetry\Distro\log_feature(
                0 /* $isForced */,
                $statementLevel,
                $prodLogFeature,
                self::processSourceCodeFilePathForLog($file),
                $line,
                self::processClassFunctionNameForLog($class, $func),
                $message
            );
        } else {
            (self::$formatAndWrite)(
                $statementLevel,
                $prodLogFeature,
                self::processSourceCodeFilePathForLog($file),
                $line,
                $func,
                $message
            );
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public static function possiblySecuritySensitive(mixed $value): mixed
    {
        return self::isEnabledForLevel(BootstrapStageLogLevelUtil::LEVEL_TRACE) ? $value : 'REDACTED (POSSIBLY SECURITY SENSITIVE) DATA';
    }
}
