<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\SystemTime;

final class AppCodeInvocation
{
    /** @var AppCodeHostParams[] */
    public array $appCodeHostsParams;
    public SystemTime $timestampAfter;

    public function __construct(
        public readonly AppCodeRequestParams $appCodeRequestParams,
        public readonly SystemTime $timestampBefore
    ) {
    }

    public function after(): void
    {
        $this->timestampAfter = AmbientContextForTests::clock()->getSystemClockCurrentTime();
    }
}
