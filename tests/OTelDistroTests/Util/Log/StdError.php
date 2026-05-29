<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;

final class StdError extends StdWriteStreamBase
{
    use SingletonInstanceTrait;

    private function __construct()
    {
        parent::__construct('stderr');
    }
}
