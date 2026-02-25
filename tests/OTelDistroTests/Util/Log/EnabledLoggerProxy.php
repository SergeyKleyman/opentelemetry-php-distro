<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Log\LogLevel;
use Throwable;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class EnabledLoggerProxy
{
    private ?bool $includeStackTrace = null;

    public function __construct(
        private readonly LogLevel $statementLevel,
        private readonly int $srcCodeLine,
        private readonly string $srcCodeFunc,
        private readonly LoggerData $loggerData
    ) {
    }

    public function includeStackTrace(bool $shouldIncludeStackTrace = true): self
    {
        $this->includeStackTrace = $shouldIncludeStackTrace;
        return $this;
    }

    /**
     * @param array<string, mixed> $statementCtx
     */
    public function log(string $message, array $statementCtx = []): bool
    {
        $this->loggerData->backend->log(
            $this->statementLevel,
            $message,
            $statementCtx,
            $this->srcCodeLine,
            $this->srcCodeFunc,
            $this->loggerData,
            $this->includeStackTrace,
            numberOfStackFramesToSkip: 1
        );
        // return dummy bool to suppress PHPStan's "Right side of && is always false"
        return true;
    }

    /**
     * @param array<string, mixed> $statementCtx
     *
     * @noinspection PhpUnused
     */
    public function logThrowable(Throwable $throwable, string $message, array $statementCtx = []): bool
    {
        $this->loggerData->backend->log(
            $this->statementLevel,
            $message,
            $statementCtx + ['throwable' => $throwable],
            $this->srcCodeLine,
            $this->srcCodeFunc,
            $this->loggerData,
            $this->includeStackTrace,
            numberOfStackFramesToSkip: 1
        );
        // return dummy bool to suppress PHPStan's "Right side of && is always false"
        return true;
    }
}
