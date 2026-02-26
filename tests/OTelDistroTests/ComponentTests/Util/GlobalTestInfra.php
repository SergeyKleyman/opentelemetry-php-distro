<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Log\LogLevel;
use OTelDistroTests\Util\ClassNameUtil;
use PHPUnit\Framework\Assert;

final class GlobalTestInfra
{
    protected ResourcesCleanerHandle $resourcesCleaner;
    protected MockOTelCollectorHandle $mockOTelCollector;

    /** @var int[] */
    private array $portsInUse = [];

    public function __construct()
    {
        $this->resourcesCleaner = $this->startResourcesCleaner();
        $this->mockOTelCollector = $this->startMockOTelCollector($this->resourcesCleaner);
    }

    public function onTestStart(): void
    {
        $this->cleanTestScoped();
    }

    public function onTestEnd(): void
    {
        $this->cleanTestScoped();
    }

    public function resetLogLevel(LogLevel $newVal): void
    {
        $this->mockOTelCollector->resetLogLevel($newVal);
        $this->resourcesCleaner->resetLogLevel($newVal);
    }

    private function cleanTestScoped(): void
    {
        $this->mockOTelCollector->cleanTestScoped();
        $this->resourcesCleaner->cleanTestScoped();
    }

    public function getResourcesCleaner(): ResourcesCleanerHandle
    {
        return $this->resourcesCleaner;
    }

    public function getMockOTelCollector(): MockOTelCollectorHandle
    {
        return $this->mockOTelCollector;
    }

    /**
     * @return int[]
     */
    public function getPortsInUse(): array
    {
        return $this->portsInUse;
    }

    /**
     * @param int[] $ports
     *
     * @return void
     */
    private function addPortsInUse(array $ports): void
    {
        foreach ($ports as $port) {
            Assert::assertNotContains($port, $this->portsInUse);
            $this->portsInUse[] = $port;
        }
    }

    private function startResourcesCleaner(): ResourcesCleanerHandle
    {
        $httpServerHandle = TestInfraHttpServerStarter::startTestInfraHttpServer(
            dbgProcessNamePrefix: ClassNameUtil::fqToShort(ResourcesCleaner::class),
            runScriptName: 'runResourcesCleaner.php',
            portsInUse: $this->portsInUse,
            portsToAllocateCount: 1,
            resourcesCleaner: null,
        );
        $this->addPortsInUse($httpServerHandle->ports);
        return new ResourcesCleanerHandle($httpServerHandle);
    }

    private function startMockOTelCollector(ResourcesCleanerHandle $resourcesCleaner): MockOTelCollectorHandle
    {
        $httpServerHandle = TestInfraHttpServerStarter::startTestInfraHttpServer(
            dbgProcessNamePrefix: ClassNameUtil::fqToShort(MockOTelCollector::class),
            runScriptName: 'runMockOTelCollector.php',
            portsInUse: $this->portsInUse,
            portsToAllocateCount: 2,
            resourcesCleaner: $resourcesCleaner,
        );
        $this->addPortsInUse($httpServerHandle->ports);
        return new MockOTelCollectorHandle($httpServerHandle);
    }
}
