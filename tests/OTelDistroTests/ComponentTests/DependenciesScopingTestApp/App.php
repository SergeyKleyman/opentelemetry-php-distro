<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\DependenciesScopingTestApp;

use Closure;
use OpenTelemetry\Distro\BootstrapStageLogLevelUtil;
use ReflectionClass;
use ReflectionFunction;
use RuntimeException;
use Throwable;

final class App
{
    private static ?int $maxEnabledLogLevel = null;

    private static function parseLogLevelConfig(): void
    {
        if (!class_exists(BootstrapStageLogLevelUtil::class)) {
            $testsRepoRootDirPath = self::getEnvVar(Shared::buildEnvVarName(Shared::TESTS_REPO_ROOT_DIR_PATH_ENV_VAR_NAME_SUFFIX));
            require $testsRepoRootDirPath . '/prod/php/OpenTelemetry/Distro/BootstrapStageLogLevelUtil.php';
        }
        self::$maxEnabledLogLevel = BootstrapStageLogLevelUtil::levelStringToInt(self::getEnvVar(Shared::buildEnvVarName(Shared::LOG_LEVEL_ENV_VAR_NAME_SUFFIX)));
    }

    private static function writeLineToStdErr(string $text): void
    {
        /** @var ?bool $isStdErrDefined */
        static $isStdErrDefined = null;
        if ($isStdErrDefined === null) {
            if (defined('STDERR')) {
                $isStdErrDefined = true;
            } else {
                $openedFileResource = fopen('php://stderr', 'w');
                $isStdErrDefined = is_resource($openedFileResource);
                if ($isStdErrDefined) {
                    define('STDERR', fopen('php://stderr', 'w'));
                }
            }
        }

        if ($isStdErrDefined) {
            fwrite(STDERR, $text . PHP_EOL);
        }
    }

    /**
     * @param array<string, mixed>  $context
     */
    private static function concatMessageAndContext(string $msg, array $context = []): string
    {
        return $msg . (count($context) === 0 ? '' : (' ; ' . json_encode($context)));
    }

    /**
     * @param array<string, mixed>  $context
     */
    private static function logWithLevel(int $level, int $srcCodeLine, string $message, array $context = []): void
    {
        if ($level > self::$maxEnabledLogLevel) {
            return;
        }

        $formattedStatement = Shared::APP_LOG_LINE_PREFIX;
        $formattedStatement .=  ' ' . '[' . BootstrapStageLogLevelUtil::levelIntToString($level) . ']';
        $formattedStatement .=  ' ' . '[' . __FILE__ . ':' . $srcCodeLine . ']';
        $formattedStatement .=  ' ' . self::concatMessageAndContext($message, $context);
        self::writeLineToStdErr($formattedStatement);
    }

    /**
     * @param array<string, mixed>  $context
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private static function logDebug(int $srcCodeLine, string $message, array $context = []): void
    {
        self::logWithLevel(BootstrapStageLogLevelUtil::LEVEL_DEBUG, $srcCodeLine, $message, $context);
    }

    /**
     * @param array<string, mixed>  $context
     *
     * @noinspection PhpSameParameterValueInspection
     */
    private static function logWarning(int $srcCodeLine, string $message, array $context = []): void
    {
        self::logWithLevel(BootstrapStageLogLevelUtil::LEVEL_WARNING, $srcCodeLine, $message, $context);
    }

    /**
     * @param array<string, mixed>  $context
     *
     * @phpstan-assert true $cond
     * @phpstan-return ($cond is true ? void : never)
     */
    public static function assert(bool $cond, string $failedMsg, array $context = [])
    {
        if (!$cond) {
            throw new RuntimeException(self::concatMessageAndContext($failedMsg, $context));
        }
    }

    /**
     * @param mixed $actualVal
     *
     * @return array<array-key, mixed>
     */
    public static function assertIsArray(mixed $actualVal): array
    {
        self::assert(is_array($actualVal), "value is not an array", ["value type" => get_debug_type($actualVal), 'value' => $actualVal]);
        /** @var array<array-key, mixed> $actualVal */
        return $actualVal;
    }

    public static function assertIsString(mixed $actualVal): string
    {
        self::assert(is_string($actualVal), "value is not an string", ["value type" => get_debug_type($actualVal), 'value' => $actualVal]);
        /** @var string $actualVal */
        return $actualVal;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $array
     *
     * @return TValue
     */
    public static function assertArrayHasKey(array $array, string $key): mixed
    {
        self::assert(array_key_exists($key, $array), "array does not have key $key", compact('array', 'key'));
        return $array[$key];
    }

    private static function getEnvVar(string $envVarName): string
    {
        $envVarVal = getenv($envVarName);
        self::assert(is_string($envVarVal), 'getenv() return value is not a string', compact('envVarName', 'envVarVal') + ['envVarVal type' => get_debug_type($envVarVal)]);
        /**  */
        return $envVarVal;
    }

    private static function stringToBool(string $boolAsString): bool
    {
        /** @var list<string> $trueValues */
        static $trueValues = ['true', 'yes', 'on', '1'];
        if (in_array($boolAsString, $trueValues, strict: true)) {
            return true;
        }
        return false;
    }

    private static function isDistroEnabled(): bool
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        return (bool)\OpenTelemetry\Distro\get_config_option_by_name(Shared::DISTRO_ENABLED_CFG_OPT_NAME);
    }

    private static function isScopingEnabled(): bool
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        return (bool)\OpenTelemetry\Distro\get_config_option_by_name(Shared::DEBUG_SCOPER_ENABLED_CFG_OPT_NAME);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $unscopedClassName
     *
     * @return class-string<T>
     */
    private static function adaptClassNameScoping(string $unscopedClassName, bool $isScoped): string
    {
        return ($isScoped ? (Shared::SCOPING_PREFIX . '\\') : '') . $unscopedClassName; // @phpstan-ignore return.type
    }

    private static function putFileContents(string $filePath, string $contents): void
    {
        $filePutContentsRetVal = file_put_contents($filePath, $contents);
        self::assert(is_int($filePutContentsRetVal), 'file_put_contents return value is not int', compact('filePutContentsRetVal'));
        $numberOfBytesWritten = intval($filePutContentsRetVal);
        $numberOfBytesInContents = strlen($contents);
        self::assert($numberOfBytesInContents === $numberOfBytesWritten, '', compact('numberOfBytesInContents', 'numberOfBytesWritten', 'contents'));
    }

    private static function normalizePath(string $path): string
    {
        return self::assertIsString(realpath($path));
    }

    private static function getDistroVendorDir(): string
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        return self::normalizePath(self::adaptClassNameScoping(\OpenTelemetry\Distro\VendorDir::class, isScoped: self::isScopingEnabled())::$fullPath);
    }

    private static function getAppVendorDir(): string
    {
        return self::normalizePath(__DIR__ . DIRECTORY_SEPARATOR . 'vendor');
    }

    private static function getInstalledVersion(string $packageName, string $vendorDir): string
    {
        $installedPhpFilePath = $vendorDir . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.php';
        self::assert(file_exists($installedPhpFilePath), "$installedPhpFilePath file does not exist", compact('installedPhpFilePath'));
        $installedMap = require($installedPhpFilePath);
        $versionsSection = self::assertArrayHasKey(self::assertIsArray($installedMap), 'versions');
        $packageSection = self::assertArrayHasKey(self::assertIsArray($versionsSection), $packageName);
        return self::assertIsString(self::assertArrayHasKey(self::assertIsArray($packageSection), 'pretty_version'));
    }

    /**
     * @return array<string, array<'Distro'|'App', string>>
     */
    private static function generatePackagesVersions(): array
    {
        $isWithDistroVariants = [false];
        if (self::isDistroEnabled()) {
            $isWithDistroVariants[] = true;
        }
        $result = [];
        foreach (Shared::ALL_PACKAGE_NAMES as $packageName) {
            $result[$packageName] = [];
            foreach ($isWithDistroVariants as $isWithDistro) {
                $result[$packageName][Shared::buildDistroOrAppKey($isWithDistro)] = self::getInstalledVersion($packageName, $isWithDistro ? self::getDistroVendorDir() : self::getAppVendorDir());
            }
        }
        return $result;
    }

    /**
     * @return array<string, array<'scoped'|'not scoped', string>>
     */
    private static function generateClassesSourceCodeFilesPaths(): array
    {
        $isScopedVariants = [false];
        if (self::isDistroEnabled() && self::isScopingEnabled()) {
            $isScopedVariants[] = true;
        }
        $result = [];
        foreach (Shared::ALL_CLASS_NAMES as $fqClassName) {
            $result[$fqClassName] = [];
            foreach ($isScopedVariants as $isScoped) {
                $reflClass = new ReflectionClass(self::adaptClassNameScoping($fqClassName, $isScoped));
                $result[$fqClassName][Shared::buildScopedKey($isScoped)] = self::assertIsString($reflClass->getFileName());
            }
        }
        return $result;
    }

    /**
     * @return array<'scoped'|'not scoped', bool>
     */
    private static function generatePsrLogHasReturnType(): array
    {
        $isScopedVariants = [false];
        if (self::isDistroEnabled() && self::isScopingEnabled()) {
            $isScopedVariants[] = true;
        }

        $result = [];
        foreach ($isScopedVariants as $isScoped) {
            $reflClass = new ReflectionClass(self::adaptClassNameScoping(Shared::PSR_LOG_ABSTRACT_LOGGER_CLASS_NAME, $isScoped));
            /**
             * @see https://github.com/php-fig/log/blob/2.0.0/src/LoggerTrait.php#L23
             * @see https://github.com/php-fig/log/blob/3.0.0/src/LoggerTrait.php#L23
             */
            $result[Shared::buildScopedKey($isScoped)] = ($reflClass->getMethod(Shared::PSR_LOG_ABSTRACT_LOGGER_METHOD_NAME)->getReturnType() !== null);
        }
        return $result;
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @phpstan-param TKey                 $key
     * @phpstan-param TValue               $value
     * @phpstan-param array<TKey, TValue> &$array
     */
    public static function addAssertingKeyNew(string|int $key, mixed $value, /* in,out */ array &$array): void
    {
        self::assert(!array_key_exists($key, $array), 'array already has the key', compact('key', 'value', 'array'));
        $array[$key] = $value;
    }

    private static function toJsonEncodable(mixed $val): mixed
    {
        if (is_scalar($val) || ($val === null)) {
            return $val;
        }

        if ($val instanceof Closure) {
            return ['class' => get_class($val), 'source code file' => (new ReflectionFunction($val))->getFileName()];
        }

        if (is_object($val)) {
            return ['class' => get_class($val), 'source code file' => (new ReflectionClass($val))->getFileName()];
        }

        if (is_array($val)) {
            return array_map(fn($arrVal) => self::toJsonEncodable($arrVal), $val);
        }

        return ['type' => get_debug_type($val)];
    }

    /**
     * @return array<string, mixed>
     */
    private static function generateAuxOutput(): array
    {
        $appCodeAuxOutput = [
            Shared::DISTRO_ENABLED_CFG_OPT_NAME => self::isDistroEnabled(),
            Shared::DEBUG_SCOPER_ENABLED_CFG_OPT_NAME => self::isScopingEnabled(),
            Shared::APP_VENDOR_DIR_PATH_KEY => self::getAppVendorDir(),
            'spl_autoload_functions()' => self::toJsonEncodable(spl_autoload_functions()),
            'spl_autoload_extensions()' => spl_autoload_extensions(),
        ];

        if (self::isDistroEnabled()) {
            $appCodeAuxOutput[Shared::DISTRO_VENDOR_DIR_PATH_KEY] = self::getDistroVendorDir();
        }

        self::addAssertingKeyNew(Shared::PACKAGES_VERSIONS_KEY, self::generatePackagesVersions(), /* ref */ $appCodeAuxOutput);
        self::addAssertingKeyNew(Shared::CLASSES_SOURCE_CODE_FILES_PATHS_KEY, self::generateClassesSourceCodeFilesPaths(), /* ref */ $appCodeAuxOutput);
        self::addAssertingKeyNew(Shared::PSR_LOG_HAS_RETURN_TYPE_KEY, self::generatePsrLogHasReturnType(), /* ref */ $appCodeAuxOutput);

        return $appCodeAuxOutput;
    }

    /**
     * @throws Throwable
     */
    private static function usePsrLoggerImpl(bool $isCompatibleWithNewPsrLog): void
    {
        if ($isCompatibleWithNewPsrLog) {
            require __DIR__ . DIRECTORY_SEPARATOR . 'CompatibleWithPsrLogReturnType.php';
            $logger = new CompatibleWithPsrLogReturnType();
        } else {
            require __DIR__ . DIRECTORY_SEPARATOR . 'IncompatibleNewPsrLogReturnType.php';
            $logger = new IncompatibleNewPsrLogReturnType();
        }
        $logger->debug('Dummy message');
    }

    public static function run(): void
    {
        self::parseLogLevelConfig();

        $appCodeAuxOutput = self::generateAuxOutput();

        $appCodeAuxOutputFilePath = self::getEnvVar(Shared::buildEnvVarName(Shared::APP_CODE_AUX_OUTPUT_FILE_PATH_ENV_VAR_NAME_SUFFIX));
        self::putFileContents($appCodeAuxOutputFilePath, self::assertIsString(json_encode($appCodeAuxOutput)));
        self::logDebug(__LINE__, 'Written app code aux output', compact('appCodeAuxOutput', 'appCodeAuxOutputFilePath'));

        $isCompatibleWithNewPsrLog = self::stringToBool(self::getEnvVar(Shared::buildEnvVarName(Shared::IS_APP_COMPATIBLE_WITH_PSR_LOG_RETURN_TYPE_ENV_VAR_NAME_SUFFIX)));
        if (!$isCompatibleWithNewPsrLog) {
            self::logWarning(__LINE__, 'About to use psr/log in a way that is expected to fail...');
        }
        self::usePsrLoggerImpl($isCompatibleWithNewPsrLog);
    }
}
