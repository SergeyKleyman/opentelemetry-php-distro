<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Ds\Set;
use OTelDistroTests\Util\HttpMethods;
use OTelDistroTests\Util\HttpStatusCodes;
use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

/**
 * @phpstan-import-type Pid from ProcessUtil
 * @phpstan-type SetOfPids Set<Pid>
 */
class HttpServerHandle implements LoggableInterface
{
    use LoggableTrait;

    public const CLIENT_LOCALHOST_ADDRESS = '127.0.0.1';
    public const SERVER_LOCALHOST_ADDRESS = self::CLIENT_LOCALHOST_ADDRESS;
    public const STATUS_CHECK_URI_PATH = TestInfraHttpServerProcessBase::BASE_URI_PATH . 'status_check';
    public const PID_KEY = 'pid';

    /**
     * @phpstan-param SetOfPids $serverPids
     * @param list<int> $ports
     */
    public function __construct(
        public readonly string $dbgProcessName,
        public readonly Set $serverPids,
        public readonly string $serverId,
        public readonly array $ports
    ) {
    }

    public function getMainPort(): int
    {
        Assert::assertNotEmpty($this->ports);
        return $this->ports[0];
    }

    /**
     * @param array<string, string> $headers
     */
    public function sendRequest(string $httpMethod, string $path, array $headers = []): ResponseInterface
    {
        return HttpClientUtilForTests::sendRequest(
            $httpMethod,
            new UrlParts(port: $this->getMainPort(), path: $path),
            new TestInfraDataPerRequest(serverId: $this->serverId),
            $headers
        );
    }

    protected function sendPostRequestAssertSuccessResponse(string $urlPath): void
    {
        $response = $this->sendRequest(HttpMethods::POST, $urlPath);
        Assert::assertSame(HttpStatusCodes::OK, $response->getStatusCode());
    }

    public function cleanTestScoped(): void
    {
        $this->sendPostRequestAssertSuccessResponse(TestInfraHttpServerProcessBase::CLEAN_TEST_SCOPED_URI_PATH);
    }
}
