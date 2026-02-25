<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\TimerInterface;

final class MockOTelCollectorPendingDataRequest
{
    /**
     * @param callable(ResponseInterface): void $callToSendResponse
     */
    public function __construct(
        public readonly int $fromIndex,
        public readonly mixed $callToSendResponse,
        public readonly TimerInterface $timer
    ) {
    }
}
