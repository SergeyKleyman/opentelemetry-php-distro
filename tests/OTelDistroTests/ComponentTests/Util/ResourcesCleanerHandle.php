<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\ClassNameUtil;

final class ResourcesCleanerHandle extends TestInfraHttpServerHandleBase
{
    private ResourcesClient $resourcesClient;

    public function __construct(HttpServerHandle $httpSpawnedProcessHandle)
    {
        parent::__construct(
            ClassNameUtil::fqToShort(ResourcesCleaner::class) /* <- dbgServerDesc */,
            $httpSpawnedProcessHandle,
        );

        $this->resourcesClient = new ResourcesClient($this->spawnedProcessInternalId, $this->getMainPort());
    }

    public function getClient(): ResourcesClient
    {
        return $this->resourcesClient;
    }
}
