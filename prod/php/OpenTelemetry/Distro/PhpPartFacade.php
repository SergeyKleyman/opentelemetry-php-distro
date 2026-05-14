<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

use OpenTelemetry\Distro\HttpTransport\NativeHttpTransportFactory;
use OpenTelemetry\Distro\InferredSpans\InferredSpans;
use OpenTelemetry\Distro\Log\NativeLogWriter;
use OpenTelemetry\Distro\Util\BoolUtil;
use OpenTelemetry\Distro\Util\HiddenConstructorTrait;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Distro\Util\OTelUtil;
use OpenTelemetry\Distro\Util\TextUtil;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\SdkAutoloader;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Version;
use RuntimeException;
use Throwable;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * Called by the extension
 */
final class PhpPartFacade
{
    use BootstrapStageLoggingClassTrait;
    /**
     * Constructor is hidden because instance() should be used instead
     */
    use HiddenConstructorTrait;

    public static bool $wasBootstrapCalled = false;

    private static ?self $singletonInstance = null;
    private static bool $rootSpanEnded = false;
    private static ?VendorCustomizationsInterface $vendorCustomizations = null;
    /** @var RemoteConfigConsumerInterface[] */
    private static array $remoteConfigConsumers = [];
    private ?InferredSpans $inferredSpans = null;

    private const IS_DISTRO_ENABLED_ENV_VAR_NAME = 'OTEL_PHP_ENABLED';
    public const USER_BOOTSTRAP_PHP_FILE_OPT_NAME = 'user_bootstrap_php_file';

    /**
     * Called by the extension
     *
     * @param string $nativePartVersion
     * @param int    $maxEnabledLogLevel
     * @param float  $requestInitStartTime
     *
     * @return bool
     */
    public static function bootstrap(string $nativePartVersion, int $maxEnabledLogLevel, float $requestInitStartTime): bool
    {
        self::$wasBootstrapCalled = true;

        require __DIR__ . DIRECTORY_SEPARATOR . 'BootstrapStageLogger.php';
        require __DIR__ . DIRECTORY_SEPARATOR . 'Util/StaticClassTrait.php';
        require __DIR__ . DIRECTORY_SEPARATOR . 'Util/BoolUtil.php';

        BootstrapStageLogger::configure($maxEnabledLogLevel, __DIR__, __NAMESPACE__);
        self::logDebug(__LINE__, __FUNCTION__, 'Starting bootstrap sequence...', compact('nativePartVersion', 'maxEnabledLogLevel', 'requestInitStartTime'));

        if (!self::isDistroEnabled()) {
            self::logCritical(__LINE__, __FUNCTION__, __FUNCTION__ . '() is called but Distro is DISABLED - aborting bootstrap sequence');
            return false;
        }

        if (self::$singletonInstance !== null) {
            self::logCritical(__LINE__, __FUNCTION__, __FUNCTION__ . '() is called even though singleton instance is already created (probably ' . __FUNCTION__ . '() is called more than once)');
            return false;
        }

        try {
            require __DIR__ . DIRECTORY_SEPARATOR . 'AutoloaderDistroOTelClasses.php';
            AutoloaderDistroOTelClasses::register(__NAMESPACE__, __DIR__);

            InstrumentationBridge::singletonInstance()->bootstrap();

            ///////////////////////////////////////////////////////////////////////////
            // TODO: Sergey Kleyman: BEGIN: REMOVE: ::
            ///////////////////////////////////////
            self::testHooking();
            ///////////////////////////////////////
            // END: REMOVE
            ////////////////////////////////////////////////////////////////////////////

            self::prepareForOTelSdk();

            self::registerAutoloaderForVendorDir();

            // User's bootstrap .php file might register remote config handler so it has to be called before remote config handler
            self::loadUserBootstrapPhpFile();
            // RemoteConfigHandler::fetchAndApply depends on OTel SDK so it has to be called after autoloader for OTel SDK is registered
            RemoteConfigHandler::fetchAndApply();
            // OverrideOTelSdkResourceAttributes::register depends on OTel SDK so it has to be called after autoloader for OTel SDK is registered
            OverrideOTelSdkResourceAttributes::register($nativePartVersion, self::$vendorCustomizations);
            self::registerNativeOtlpSerializer();
            self::registerAsyncTransportFactory();
            self::registerOtelLogWriter();

            /** @noinspection PhpInternalEntityUsedInspection */
            if (SdkAutoloader::isExcludedUrl()) {
                self::logDebug(__LINE__, __FUNCTION__, 'URL is excluded');
                return false;
            }

            Traces\RootSpan::startRootSpan(function () {
                PhpPartFacade::$rootSpanEnded = true;
                if (PhpPartFacade::$singletonInstance && PhpPartFacade::$singletonInstance->inferredSpans) {
                    PhpPartFacade::$singletonInstance->inferredSpans->shutdown();
                }
            });

            self::$singletonInstance = new self();

            /**
             * Use fully qualified names for functions implemented by the extension to make sure scoper correctly detects them
             * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
             */
            if (\OpenTelemetry\Distro\get_config_option_by_name('inferred_spans_enabled')) {
                /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
                self::$singletonInstance->inferredSpans = new InferredSpans(
                    (bool)\OpenTelemetry\Distro\get_config_option_by_name('inferred_spans_reduction_enabled'),
                    (bool)\OpenTelemetry\Distro\get_config_option_by_name('inferred_spans_stacktrace_enabled'),
                    \OpenTelemetry\Distro\get_config_option_by_name('inferred_spans_min_duration') // @phpstan-ignore argument.type
                );
            }
        } catch (Throwable $throwable) {
            self::logCriticalThrowable(__LINE__, __FUNCTION__, $throwable, 'One of the steps in bootstrap sequence has thrown');
            return false;
        }

        self::logDebug(__LINE__, __FUNCTION__, 'Successfully completed bootstrap sequence');
        return true;
    }

    /**
     * Called by the extension
     *
     * @noinspection PhpUnused
     */
    public static function inferredSpans(int $durationMs, bool $internalFunction): bool
    {
        if (self::$singletonInstance === null) {
            self::logDebug(__LINE__, __FUNCTION__, 'Missing facade');
            return true;
        }

        if (self::$singletonInstance->inferredSpans === null) {
            self::logDebug(__LINE__, __FUNCTION__, 'Missing inferred spans instance');
            return true;
        }
        self::$singletonInstance->inferredSpans->captureStackTrace($durationMs, $internalFunction);

        return true;
    }

    private static function isDistroEnabled(): bool
    {
        return self::getBoolEnvVar(self::IS_DISTRO_ENABLED_ENV_VAR_NAME, default: true);
    }

    public static function getBoolEnvVar(string $envVarName, bool $default): bool
    {
        $envVarVal = getenv($envVarName);
        if (is_string($envVarVal) && (($parsedVal = BoolUtil::parseValue($envVarVal)) !== null)) {
            return $parsedVal;
        }
        return $default;
    }

    /**
     * @param non-empty-string $envVarName
     */
    public static function setEnvVar(string $envVarName, string $envVarValue): void
    {
        if (!putenv($envVarName . '=' . $envVarValue)) {
            throw new RuntimeException('putenv returned false; $envVarName: ' . $envVarName . '; envVarValue: ' . $envVarValue);
        }
    }

    /**
     * Registers vendor-specific customizations. Must be called BEFORE bootstrap().
     */
    public static function setVendorCustomizations(VendorCustomizationsInterface $vendor): void
    {
        self::$vendorCustomizations = $vendor;
    }

    public static function getVendorCustomizations(): ?VendorCustomizationsInterface
    {
        return self::$vendorCustomizations;
    }

    /**
     * Registers a remote config consumer. Must be called BEFORE bootstrap().
     */
    public static function registerRemoteConfigConsumer(RemoteConfigConsumerInterface $consumer): void
    {
        self::$remoteConfigConsumers[] = $consumer;
    }

    /**
     * @return RemoteConfigConsumerInterface[]
     */
    public static function getRemoteConfigConsumers(): array
    {
        return self::$remoteConfigConsumers;
    }

    private static function prepareForOTelSdk(): void
    {
        self::setEnvVar('OTEL_PHP_AUTOLOAD_ENABLED', 'true');

        // Unset COMPOSER_DEV_MODE to prevent OTel SDK's ComposerHandler::isRunning() from returning true,
        // which would skip SdkAutoloader::autoload() and result in no TracerProvider being created.
        // Currently, this is handled by the test infrastructure (AppCodeHostParams::filterBaseEnvVars),
        // but if the issue occurs in production deployments, uncomment the line below.
        // putenv('COMPOSER_DEV_MODE');
    }

    private static function registerAutoloaderForVendorDir(): void
    {
        $vendorAutoloadPhp = VendorDir::$fullPath . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!file_exists($vendorAutoloadPhp)) {
            throw new RuntimeException("File $vendorAutoloadPhp does not exist");
        }
        self::logDebug(__LINE__, __FUNCTION__, 'Before require', compact('vendorAutoloadPhp'));
        require $vendorAutoloadPhp;

        self::logDebug(__LINE__, __FUNCTION__, 'Finished successfully');
    }

    private static function registerAsyncTransportFactory(): void
    {
        /**
         * Use fully qualified names for functions implemented by the extension to make sure scoper correctly detects them
         * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
         */
        if (\OpenTelemetry\Distro\get_config_option_by_name('async_transport') === false) {
            self::logDebug(__LINE__, __FUNCTION__, 'OTEL_PHP_ASYNC_TRANSPORT set to false');
            return;
        }

        Registry::registerTransportFactory('http', NativeHttpTransportFactory::class, true);
    }

    private static function registerOtelLogWriter(): void
    {
        NativeLogWriter::enableLogWriter();
    }

    private static function registerNativeOtlpSerializer(): void
    {
        /**
         * Use fully qualified names for functions implemented by the extension to make sure scoper correctly detects them
         * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
         */
        if (\OpenTelemetry\Distro\get_config_option_by_name('native_otlp_serializer_enabled') === false) {
            self::logDebug(__LINE__, __FUNCTION__, 'OTEL_PHP_NATIVE_OTLP_SERIALIZER_ENABLED set to false');
        } else {
            // Load classes such as \OpenTelemetry\Contrib\Otlp\SpanExporter to shadow the ones in SDK
            $otelOtlpDir = ProdPhpDir::$fullPath . DIRECTORY_SEPARATOR . 'Contrib' . DIRECTORY_SEPARATOR . 'Otlp';
            foreach (['SpanExporter', 'LogsExporter', 'MetricExporter'] as $exporter) {
                require $otelOtlpDir . DIRECTORY_SEPARATOR . $exporter . '.php';
            }
        }
    }

    /**
     * Called by the extension
     *
     * @noinspection PhpUnused
     */
    public static function handleError(int $type, string $errorFilename, int $errorLineno, string $message): void
    {
        self::logDebug(__LINE__, __FUNCTION__, 'Entered', compact('type', 'errorFilename', 'errorLineno', 'message'));
    }

    /**
     * Called by the extension
     *
     * @noinspection PhpUnused
     */
    public static function shutdown(): void
    {
        self::$singletonInstance = null;
    }

    /**
     * Called by the extension
     *
     * @param array<mixed> $params
     *
     * @noinspection PhpUnused, PhpUnusedParameterInspection
     */
    public static function debugPreHook(mixed $object, array $params, ?string $class, string $function, ?string $filename, ?int $lineno): void
    {
        if (self::$rootSpanEnded) {
            return;
        }

        $tracer = Globals::tracerProvider()->getTracer(
            'io.opentelemetry.php.distro.debug',
            null,
            Version::VERSION_1_25_0->url(),
        );

        $parent = Context::getCurrent();
        $fqFunctionName = OTelUtil::buildFqFunctionName($class, $function);
        $span = $tracer->spanBuilder($fqFunctionName) // @phpstan-ignore argument.type
                       ->setSpanKind(SpanKind::KIND_CLIENT)
                       ->setParent($parent)
                       ->setAttribute(CodeAttributes::CODE_FUNCTION_NAME, $fqFunctionName)
                       ->setAttribute(CodeAttributes::CODE_FILE_PATH, $filename)
                       ->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $lineno)
                       ->setAttribute('call.arguments', print_r($params, true))
                       ->startSpan();

        $context = $span->storeInContext($parent);
        Context::storage()->attach($context);
    }

    /**
     * Called by the extension
     *
     * @param array<mixed> $params
     *
     * @noinspection PhpUnused, PhpUnusedParameterInspection
     */
    public static function debugPostHook(mixed $object, array $params, mixed $retval, ?Throwable $exception): void
    {
        if (self::$rootSpanEnded) {
            return;
        }

        $scope = Context::storage()->scope();
        if (!$scope) {
            return;
        }

        $scope->detach();
        $span = Span::fromContext($scope->context());
        $span->setAttribute('call.return_value', print_r($retval, true));

        if ($exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }

        $span->end();
    }

    private static function loadUserBootstrapPhpFile(): void
    {
        /**
         * Use fully qualified names for functions implemented by the extension to make sure scoper correctly detects them
         * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
         */
        $userBootstrapPhpFile = \OpenTelemetry\Distro\get_config_option_by_name(self::USER_BOOTSTRAP_PHP_FILE_OPT_NAME);
        if (!is_string($userBootstrapPhpFile)) {
            self::logError(
                __LINE__,
                __FUNCTION__,
                self::USER_BOOTSTRAP_PHP_FILE_OPT_NAME . ' configuration option value is not a string',
                ['actual type' => get_debug_type($userBootstrapPhpFile), 'actual value' => $userBootstrapPhpFile]
            );
            return;
        }
        if (TextUtil::isEmptyString($userBootstrapPhpFile)) {
            self::logDebug(__LINE__, __FUNCTION__, self::USER_BOOTSTRAP_PHP_FILE_OPT_NAME . ' configuration option is not set');
            return;
        }

        if (!file_exists($userBootstrapPhpFile)) {
            self::logError(__LINE__, __FUNCTION__, self::USER_BOOTSTRAP_PHP_FILE_OPT_NAME . " configuration option value is a path $userBootstrapPhpFile that does not exist");
            return;
        }
        self::logDebug(__LINE__, __FUNCTION__, 'Before require', compact('userBootstrapPhpFile'));
        require $userBootstrapPhpFile;
        self::logDebug(__LINE__, __FUNCTION__, 'After require', compact('userBootstrapPhpFile'));
    }

    /**
     * Must be defined in class using BootstrapStageLoggingClassTrait
     */
    private static function getCurrentSourceCodeFile(): string
    {
        return __FILE__;
    }

    /**
     * Must be defined in class using BootstrapStageLoggingClassTrait
     */
    private static function getCurrentSourceCodeClass(): string
    {
        return __CLASS__;
    }

    ///////////////////////////////////////////////////////////////////////////
    // TODO: Sergey Kleyman: BEGIN: REMOVE: ::
    ///////////////////////////////////////
    /**
     * @return string|array<mixed>
     */
    private static function callableToDbgDesc(mixed $callback): string|array
    {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        if ($callback instanceof \Closure) {
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $reflFunc = new \ReflectionFunction($callback);
            return 'Closure defined at ' . $reflFunc->getFileName() . ':' . $reflFunc->getStartLine();
        }
        if (is_string($callback)) {
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $reflFunc = new \ReflectionFunction($callback);
            return $callback . ' function defined at ' . $reflFunc->getFileName() . ':' . $reflFunc->getStartLine();
        }
        if (is_array($callback) && (count($callback) === 2) && (is_object($callback[0]) || is_string($callback[0])) && is_string($callback[1])) {
            /** @noinspection PhpFullyQualifiedNameUsageInspection */
            $reflClass = new \ReflectionClass($callback[0]); // @phpstan-ignore argument.type
            $reflMethod = $reflClass->getMethod($callback[1]);
            return $reflClass->getName() . '::' . $reflMethod->getName() . ' method at ' . $reflMethod->getFileName() . ':' . $reflMethod->getStartLine();
        }

        return ['debug type' => get_debug_type($callback)];
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private static function valueToDbgDesc(mixed $value): mixed
    {
        if (is_scalar($value) || ($value === null)) {
            return $value;
        }

        if (is_callable($value)) {
            return self::callableToDbgDesc($value);
        }

        return ['debug type' => get_debug_type($value)];
    }

    private static string $testHookingState = '';

    /** @noinspection PhpUnusedParameterInspection */
    private static function testHookingCallback(string $callbackDbgDesc, mixed ...$args): void
    {
        $ctx = ['testHookingState' => self::$testHookingState, 'stack trace' => debug_backtrace(), 'args' => array_map(self::valueToDbgDesc(...), $args)];
        self::logInfo(__LINE__, __FUNCTION__, 'TEST: ' . $callbackDbgDesc . ' entered', $ctx);
        BootstrapStageStdErrWriter::writeLine('[INFO] TEST: ' . $callbackDbgDesc . ' entered | ' . json_encode($ctx));
    }

    /**
     * @param array<mixed> $argsForFuncToHookCall
     */
    private static function testHookingImpl(?string $fqClassToHook, string $funcToHook, array $argsForFuncToHookCall): void
    {
        $fqFuncToHookDbgDesc = (($fqClassToHook === null) ? '' : ($fqClassToHook . '::')) . $funcToHook;
        self::logInfo(__LINE__, __FUNCTION__, 'TEST: Entered', compact('fqClassToHook', 'funcToHook', 'fqFuncToHookDbgDesc'));

        $hookRetVal = InstrumentationBridge::singletonInstance()->hook(
            class: $fqClassToHook,
            function: $funcToHook,
            pre: function (
                mixed ...$args,
            ) use (
                $fqFuncToHookDbgDesc
            ): void {
                self::testHookingCallback('pre-hook for ' . $fqFuncToHookDbgDesc, ...$args);
            },
            post: function (
                mixed $thisObj,
                array $params,
                /** @noinspection PhpUnusedParameterInspection */ mixed $returnValue,
                /** @noinspection PhpUnusedParameterInspection */ ?Throwable $throwable,
            ) use (
                $fqFuncToHookDbgDesc
            ): void {
                self::testHookingCallback('post-hook for ' . $fqFuncToHookDbgDesc, ...$params);
            },
        );

        if (!$hookRetVal) {
            self::logError(__LINE__, __FUNCTION__, 'TEST: hook() return false', compact('fqFuncToHookDbgDesc'));
            return;
        }
        self::logInfo(__LINE__, __FUNCTION__, 'TEST: Registered pre-hook and post-hook for ' . $fqFuncToHookDbgDesc);

        self::$testHookingState = 'Before calling ' . $fqFuncToHookDbgDesc;
        self::logInfo(__LINE__, __FUNCTION__, 'TEST: ' . self::$testHookingState);
        if (($fqClassToHook === null) && ($funcToHook === 'spl_autoload_register')) {
            self::logInfo(__LINE__, __FUNCTION__, 'TEST: Calling spl_autoload_register');
            $funcToHookRetVal = spl_autoload_register(...$argsForFuncToHookCall); // @phpstan-ignore argument.type
        } else {
            /** @var callable(mixed ...): mixed $funcToHookCallable */
            $funcToHookCallable = ($fqClassToHook === null) ? $funcToHook : [$fqClassToHook, $funcToHook]; // @phpstan-ignore varTag.nativeType
            $funcToHookRetVal = ($funcToHookCallable)(...$argsForFuncToHookCall);
        }
        self::$testHookingState = 'After calling ' . $fqFuncToHookDbgDesc;
        self::logInfo(__LINE__, __FUNCTION__, 'TEST: ' . self::$testHookingState, compact('funcToHookRetVal'));
    }

    /**
     * @phpstan-param callable(string): void $callback
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private static function altSplAutoloadRegister(?callable $callback, bool $throw = true, bool $prepend = false): void // @phpstan-ignore method.unused
    {
        self::testHookingCallback(__FUNCTION__, ...func_get_args());
    }

    private static function testHooking(): void
    {
//        self::testHookingImpl(
//            fqClassToHook: null,
//            funcToHook: 'class_uses',
//            argsForFuncToHookCall: [/* object_or_class */ self::class]
//        );

        $autoloadCallback = function (string $class): void {
            self::testHookingCallback('autoloadCallback', ...func_get_args());
        };
        self::testHookingImpl(
            fqClassToHook: self::class,
            funcToHook: 'altSplAutoloadRegister',
            argsForFuncToHookCall: [$autoloadCallback, /* throw */ true, /* prepend */ true]
        );

        self::testHookingImpl(
            fqClassToHook: null,
            funcToHook: 'spl_autoload_register',
            argsForFuncToHookCall: [$autoloadCallback, /* throw */ true, /* prepend */ true]
        );

        $unregisterRetVal = spl_autoload_unregister($autoloadCallback);
        if (!$unregisterRetVal) {
            self::logError(__LINE__, __FUNCTION__, 'After the 1st call to spl_autoload_unregister (expected to return true)', compact('unregisterRetVal'));
        }
        $unregisterRetVal = spl_autoload_unregister($autoloadCallback);
        if ($unregisterRetVal) {
            self::logError(__LINE__, __FUNCTION__, 'After the 2nd call to spl_autoload_unregister (expected to return false)', compact('unregisterRetVal'));
        }

//        self::testHookingImpl(
//            fqClassToHook: null,
//            funcToHook: 'ctype_cntrl',
//            argsForFuncToHookCall: ["dummy text with control char \t = ctype_cntrl exepected to return true"]
//        );
    }
    ///////////////////////////////////////
    // END: REMOVE
    ////////////////////////////////////////////////////////////////////////////
}
