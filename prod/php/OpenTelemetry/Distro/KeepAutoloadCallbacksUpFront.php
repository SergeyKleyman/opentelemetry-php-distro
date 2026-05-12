<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

use OpenTelemetry\Distro\Log\LogFeature;
use RuntimeException;
use Throwable;

final class KeepAutoloadCallbacksUpFront
{
    use BootstrapStageLoggingClassTrait;

    private bool $shouldIgnoreRegisterCalls = false;

    /**
     * @param list<callable> $callbacks
     */
    public function __construct(
        private readonly InstrumentationBridge $instrumBridge,
        private array $callbacks
    ) {
        $this->hookSplAutoloadRegister();
    }

    /**
     * @param list<callable> $callbacks
     */
    public function setCallbacks(array $callbacks): void
    {
        self::unregisterCallbacks();
        $this->callbacks = $callbacks;
        self::registerCallbacks();
    }

    private function hookSplAutoloadRegister(): void
    {
        $hookRetVal = $this->instrumBridge->hook(
            class: null,
            function: 'spl_autoload_register',
            post: $this->splAutoloadRegisterPostHookToKeepDistroFirst(...),
        );

        if (!$hookRetVal) {
            throw new RuntimeException('hook() return false');
        }
    }

    /**
     * @param list<mixed> $params
     */
    private function splAutoloadRegisterPostHookToKeepDistroFirst(
        /** @noinspection PhpUnusedParameterInspection */ ?object $thisObj,
        array $params,
        mixed $returnValue,
        ?Throwable $throwable,
    ): void {
        if ($this->shouldIgnoreRegisterCalls) {
            self::logDebug(__LINE__, __FUNCTION__, 'shouldIgnoreRegisterCalls is true - not doing anything');
            return;
        }

        if ($throwable !== null) {
            self::logDebug(__LINE__, __FUNCTION__, 'Call spl_autoload_register() thrown - not doing anything', ['throwable message' => $throwable->getMessage()]);
            return;
        }

        // function spl_autoload_register(?callable $callback, bool $throw = true, bool $prepend = false): bool {}
        if (count($params) < 3) {
            self::logError(__LINE__, __FUNCTION__, 'prepend parameter is missing', ['count($params)' => count($params)]);
            return;
        }

        if (!$params[2]) {
            self::logDebug(__LINE__, __FUNCTION__, 'prepend param value is not true - not doing anything');
            return;
        }

        self::unregisterCallbacks();
        self::registerCallbacks();
    }

    private function unregisterCallbacks(): void
    {
        self::logAutoloadFunctions(__LINE__, __FUNCTION__, 'Entered');

        foreach ($this->callbacks as $callback) {
            spl_autoload_unregister($callback);
        }

        self::logAutoloadFunctions(__LINE__, __FUNCTION__, 'Exiting');
    }

    private function registerCallbacks(): void
    {
        self::logAutoloadFunctions(__LINE__, __FUNCTION__, 'Entered');

        $this->shouldIgnoreRegisterCalls = true;
        try {
            $callbacksCount = count($this->callbacks);
            for ($i = 0; $i != $callbacksCount; ++$i) {
                // iterate over callbacks array in reverse order
                spl_autoload_register($this->callbacks[$callbacksCount - $i], prepend: true);
            }
        } finally {
            $this->shouldIgnoreRegisterCalls = false;
        }

        self::logAutoloadFunctions(__LINE__, __FUNCTION__, 'Exiting');
    }

    private static function logAutoloadFunctions(int $line, string $func, string $message): void
    {
        /**
         * @var int $logLevel
         *
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        static $logLevel = BootstrapStageLogLevelUtil::LEVEL_DEBUG;
        if (self::isLogEnabledForLevel($logLevel)) {
            self::logWithLevel($logLevel, $line, $func, $message, ['spl_autoload_functions()' => SplAutoloadFunctionsLogUtil::callbacksToLoggable(spl_autoload_functions())]);
        }
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

    /**
     * Must be defined in class using BootstrapStageLoggingClassTrait
     */
    private static function getCurrentLogFeature(): int
    {
        return LogFeature::MODULE;
    }
}
