<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Log\LogLevel;
use OTelDistroTests\Util\HttpMethods;
use OTelDistroTests\Util\HttpStatusCodes;
use PHPUnit\Framework\Assert;

abstract class TestInfraHttpServerHandleBase extends HttpServerHandle
{
    public function __construct(string $dbgProcessName, HttpServerHandle $httpSpawnedProcessHandle)
    {
        parent::__construct(
            $dbgProcessName,
            $httpSpawnedProcessHandle->spawnedProcessOsId,
            $httpSpawnedProcessHandle->spawnedProcessInternalId,
            $httpSpawnedProcessHandle->ports
        );
    }

    public function resetLogLevel(LogLevel $newVal): void
    {
        $response = $this->sendRequest(
            HttpMethods::POST,
            TestInfraHttpServerProcessBase::RESET_LOG_LEVEL_URI_PATH,
            [TestInfraHttpServerProcessBase::LOG_LEVEL_HEADER_NAME => $newVal->name],
        );
        Assert::assertSame(HttpStatusCodes::OK, $response->getStatusCode());
    }

    public function cleanTestScoped(): void
    {
        $response = $this->sendRequest(HttpMethods::POST, TestInfraHttpServerProcessBase::CLEAN_TEST_SCOPED_URI_PATH);
        Assert::assertSame(HttpStatusCodes::OK, $response->getStatusCode());
    }
}
