<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Util\BoolUtil;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\HttpMethods;
use OTelDistroTests\Util\HttpStatusCodes;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\Log\Logger;

final class ResourcesCleanerClient
{
    private Logger $logger;

    public function __construct(
        private readonly string $resourcesCleanerServerId,
        private readonly int $resourcesCleanerPort
    ) {
        $this->logger = $this->buildLogger();
    }

    public function buildLogger(): Logger
    {
        return AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('this'));
    }

    /**
     * @return list<string>
     */
    public function __sleep(): array
    {
        $result = [];
        /** @var string $propName */
        foreach ($this as $propName => $_) { // @phpstan-ignore foreach.nonIterable
            if ($propName === 'logger') {
                continue;
            }
            $result[] = $propName;
        }
        return $result;
    }

    public function __wakeup(): void
    {
        $this->logger = $this->buildLogger();
    }

    public function sendProcessToTerminateRegistration(string $dbgProcessName, int $pid, bool $isTestScoped, bool $registerOrUpdate): void
    {
        $logDebug = $this->logger->inherit(compact('pid', 'isTestScoped', 'registerOrUpdate'))->logDebug(__FUNCTION__);
        $logDebug?->with(__LINE__, $registerOrUpdate ? 'Registering process to terminate' : 'Updating process to terminate registration');

        $urlPath = $registerOrUpdate ? ResourcesCleaner::REGISTER_PROCESS_TO_TERMINATE_URI_PATH : ResourcesCleaner::UPDATE_PROCESS_TO_TERMINATE_REGISTRATION_URI_PATH;
        $response = HttpClientUtilForTests::sendRequest(
            HttpMethods::POST,
            new UrlParts(port: $this->resourcesCleanerPort, path: $urlPath),
            new TestInfraDataPerRequest(serverId: $this->resourcesCleanerServerId),
            headers: [
                ResourcesCleaner::DBG_PROCESS_NAME_HEADER_NAME => $dbgProcessName,
                ResourcesCleaner::PID_HEADER_NAME => strval($pid),
                ResourcesCleaner::IS_TEST_SCOPED_HEADER_NAME => BoolUtil::toString($isTestScoped)
            ]
        );
        if ($response->getStatusCode() !== HttpStatusCodes::OK) {
            throw new ComponentTestsInfraException('Failed to register with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));
        }

        $logDebug?->with(__LINE__, 'Successfully ' . ($registerOrUpdate ? 'registered process to terminate' : 'updated process to terminate registration'));
    }

    public function registerProcessToTerminate(string $dbgProcessName, int $pid, bool $isTestScoped): void
    {
        $this->sendProcessToTerminateRegistration($dbgProcessName, $pid, $isTestScoped, registerOrUpdate: true);
    }

    /** @noinspection PhpSameParameterValueInspection */
    private function registerFileToDelete(string $fullPath, bool $isTestScoped): void
    {
        $logDebug = $this->logger->inherit()->addAllContext(compact('fullPath', 'isTestScoped'))->logDebug(__FUNCTION__);
        $logDebug?->with(__LINE__, 'Registering file to delete with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));

        $response = HttpClientUtilForTests::sendRequest(
            HttpMethods::POST,
            new UrlParts(port: $this->resourcesCleanerPort, path: ResourcesCleaner::REGISTER_FILE_TO_DELETE_URI_PATH),
            new TestInfraDataPerRequest(serverId: $this->resourcesCleanerServerId),
            [ResourcesCleaner::PATH_HEADER_NAME => $fullPath, ResourcesCleaner::IS_TEST_SCOPED_HEADER_NAME => BoolUtil::toString($isTestScoped)] /* <- headers */
        );
        if ($response->getStatusCode() !== HttpStatusCodes::OK) {
            throw new ComponentTestsInfraException('Failed to register with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));
        }

        $logDebug?->with(__LINE__, 'Successfully registered file to delete with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));
    }

    public function createTempFile(string $fileNamePrefix, bool $shouldBeDeletedOnTestExit = true): string
    {
        $tempFileFullPath = FileUtil::createTempFile($fileNamePrefix);
        if ($shouldBeDeletedOnTestExit) {
            $this->registerFileToDelete($tempFileFullPath, isTestScoped: true);
        }
        return $tempFileFullPath;
    }

    public function terminateProcessTree(int $pid): void
    {
        $logDebug = $this->logger->inherit()->addAllContext(compact('fullPath', 'isTestScoped'))->logDebug(__FUNCTION__);
        $logDebug?->with(__LINE__, 'Registering file to delete with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));

        $response = HttpClientUtilForTests::sendRequest(
            HttpMethods::POST,
            new UrlParts(port: $this->resourcesCleanerPort, path: ResourcesCleaner::REGISTER_FILE_TO_DELETE_URI_PATH),
            new TestInfraDataPerRequest(serverId: $this->resourcesCleanerServerId),
            [ResourcesCleaner::PATH_HEADER_NAME => $fullPath, ResourcesCleaner::IS_TEST_SCOPED_HEADER_NAME => BoolUtil::toString($isTestScoped)] /* <- headers */
        );
        if ($response->getStatusCode() !== HttpStatusCodes::OK) {
            throw new ComponentTestsInfraException('Failed to register with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));
        }

        $logDebug?->with(__LINE__, 'Successfully registered file to delete with ' . ClassNameUtil::fqToShort(ResourcesCleaner::class));
    }
}
