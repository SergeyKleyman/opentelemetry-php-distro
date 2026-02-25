<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Log\LogLevel;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface SinkInterface
{
    /**
     * @param array<array-key, mixed> $context
     * @param non-negative-int        $numberOfStackFramesToSkip
     */
    public function consume(
        LogLevel $statementLevel,
        string $message,
        array $context,
        string $category,
        string $srcCodeFile,
        int $srcCodeLine,
        string $srcCodeFunc,
        ?bool $includeStacktrace,
        int $numberOfStackFramesToSkip
    ): void;
}
