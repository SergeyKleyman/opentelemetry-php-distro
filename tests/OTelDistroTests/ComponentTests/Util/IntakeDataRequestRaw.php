<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\Log\LoggableTrait;
use OTelDistroTests\Util\Log\LogStreamInterface;
use OTelDistroTests\Util\MonotonicTime;
use OTelDistroTests\Util\SystemTime;

/**
 * @phpstan-type HttpHeaders array<string, string[]>
 */
final class IntakeDataRequestRaw extends AgentBackendCommEvent
{
    use LoggableTrait;

    /**
     * @param HttpHeaders $httpHeaders
     */
    public function __construct(
        MonotonicTime $monotonicTime,
        SystemTime $systemTime,
        public readonly OTelSignalType $signalType,
        public readonly array $httpHeaders,
        public readonly string $body,
    ) {
        parent::__construct($monotonicTime, $systemTime);
    }

    /**
     * @return string[]
     */
    protected static function propertiesExcludedFromLog(): array
    {
        return ['body'];
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $customToLog = ['strlen(body)' => strlen($this->body)];
        $this->toLogLoggableTraitImpl($stream, $customToLog);
    }
}
