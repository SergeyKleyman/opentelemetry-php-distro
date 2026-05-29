<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\ClassNameUtil;
use PHPUnit\Framework\Assert;

final class ResourcesCleanerHandle extends HttpServerHandle
{
    private ResourcesCleanerClient $resourcesCleanerClient;

    public function __construct(HttpServerHandle $httpHandle)
    {
        parent::__construct(
            ClassNameUtil::fqToShort(ResourcesCleaner::class) /* <- dbgServerDesc */,
            $httpHandle->serverPids,
            $httpHandle->serverId,
            $httpHandle->ports
        );

        $this->resourcesCleanerClient = new ResourcesCleanerClient($this->serverId, $this->getMainPort());
    }

    public function getClient(): ResourcesCleanerClient
    {
        return $this->resourcesCleanerClient;
    }

    public function signalAndWaitForItToExit(): void
    {
        $relatedRunningProcesses = RunningProcessesInfo::getForAllInCurrentSession()->getSubTrees($this->serverPids);

        $this->sendPostRequestAssertSuccessResponse(TestInfraHttpServerProcessBase::EXIT_URI_PATH);

        $hasExited = $relatedRunningProcesses->waitToExit(dbgProcessesSetDesc: $this->dbgProcessName, maxWaitTimeInMicroseconds: 10 * 1000 * 1000 /* 10 seconds */);
        Assert::assertTrue($hasExited);
    }
}
