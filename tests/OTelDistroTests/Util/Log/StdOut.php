<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class StdOut extends StdWriteStreamBase
{
    use SingletonInstanceTrait;

    private function __construct()
    {
        parent::__construct('stdout');
    }
}
