<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

final class AgentBackendCommEventsBlock
{
    /**
     * @param list<AgentBackendCommEvent> $events
     */
    public function __construct(
        public array $events
    ) {
    }
}
