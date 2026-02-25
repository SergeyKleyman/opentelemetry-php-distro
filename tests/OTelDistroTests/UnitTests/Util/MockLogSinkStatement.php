<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\Util;

use OpenTelemetry\Distro\Log\LogLevel;

class MockLogSinkStatement
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public LogLevel $statementLevel,
        public string $message,
        public array $context,
        public string $category,
        public string $srcCodeFile,
        public int $srcCodeLine,
        public string $srcCodeFunc,
        public ?bool $includeStacktrace,
        public int $numberOfStackFramesToSkip
    ) {
    }
}
