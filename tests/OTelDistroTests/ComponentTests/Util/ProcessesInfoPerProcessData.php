<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

/**
 * @phpstan-import-type Pid from ProcessesInfo
 */
final class ProcessesInfoPerProcessData
{
    /**
     * @phpstan-param Pid $parentPid
     */
    public function __construct(
        public readonly int $parentPid,
        public readonly string $state,
        public readonly string $commandLine,
    ) {
    }
}
