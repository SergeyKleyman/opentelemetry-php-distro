<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

final class AgentBackendConnection
{
    /**
     * @param IntakeDataRequestDeserialized[] $requests
     */
    public function __construct(
        public readonly AgentBackendConnectionStarted $started,
        public readonly array $requests
    ) {
    }
}
