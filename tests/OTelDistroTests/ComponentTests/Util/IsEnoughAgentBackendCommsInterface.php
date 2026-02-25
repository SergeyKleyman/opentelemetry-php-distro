<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

interface IsEnoughAgentBackendCommsInterface
{
    public function reasonNotEnough(AgentBackendComms $comms): ?string;
}
