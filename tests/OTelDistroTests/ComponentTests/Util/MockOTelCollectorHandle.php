<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Util\BoolUtil;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\HttpMethods;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\Log\Logger;
use PHPUnit\Framework\Assert;

final class MockOTelCollectorHandle extends TestInfraHttpServerHandleBase
{
    private readonly Logger $logger;
    private int $nextIntakeDataRequestIndexToFetch = 0;

    public function __construct(HttpServerHandle $httpSpawnedProcessHandle)
    {
        parent::__construct(
            ClassNameUtil::fqToShort(MockOTelCollector::class) /* <- dbgServerDesc */,
            $httpSpawnedProcessHandle,
        );

        $this->logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('this'));
    }

    public function getPortForAgent(): int
    {
        Assert::assertCount(2, $this->ports);
        return $this->ports[1];
    }

    /**
     * @return list<AgentBackendCommEvent>
     */
    public function fetchNewAgentBackendCommEvents(bool $shouldWait): array
    {
        $loggerProxyDebug = $this->logger->ifDebugLevelEnabledNoLine(__FUNCTION__);
        $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Starting...');

        $response = $this->sendRequest(
            HttpMethods::GET,
            MockOTelCollector::MOCK_API_URI_PREFIX . MockOTelCollector::GET_AGENT_BACKEND_COMM_EVENTS_URI_SUBPATH,
            [
                MockOTelCollector::FROM_INDEX_HEADER_NAME => strval($this->nextIntakeDataRequestIndexToFetch),
                MockOTelCollector::SHOULD_WAIT_HEADER_NAME => BoolUtil::toString($shouldWait),
            ]
        );

        $newEvents = MockOTelCollector::decodeGetAgentBackendCommEvents($response);

        if (ArrayUtilForTests::isEmpty($newEvents)) {
            $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Fetched NO new data from agent receiver events');
        } else {
            $this->nextIntakeDataRequestIndexToFetch += count($newEvents);
            $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Fetched new data from agent receiver events', ['count(newEvents)' => count($newEvents)]);
        }
        return $newEvents;
    }

    public function cleanTestScoped(): void
    {
        $this->nextIntakeDataRequestIndexToFetch = 0;

        parent::cleanTestScoped();
    }
}
