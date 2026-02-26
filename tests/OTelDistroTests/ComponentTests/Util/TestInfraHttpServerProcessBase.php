<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Log\LogLevel;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\ArrayUtilForTests;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\Config\OptionForTestsName;
use OTelDistroTests\Util\ExceptionUtil;
use OTelDistroTests\Util\HttpStatusCodes;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\Log\LoggableToString;
use OTelDistroTests\Util\Log\Logger;
use ErrorException;
use Override;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Http\HttpServer;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use Throwable;

abstract class TestInfraHttpServerProcessBase extends SpawnedProcessBase
{
    use HttpServerProcessTrait;

    public const BASE_URI_PATH = '/OTel_PHP_Distro_tests_infra/';
    public const CLEAN_TEST_SCOPED_URI_PATH = self::BASE_URI_PATH . 'clean_test_scoped';
    public const RESET_LOG_LEVEL_URI_PATH = self::BASE_URI_PATH . 'reset_log_level';
    public const EXIT_URI_PATH = self::BASE_URI_PATH . 'exit';

    public const LOG_LEVEL_HEADER_NAME = RequestHeadersRawSnapshotSource::HEADER_NAMES_PREFIX . 'LOG_LEVEL';

    private readonly Logger $logger;
    protected ?LoopInterface $reactLoop = null;

    /** @var SocketServer[] */
    protected array $serverSockets = [];

    public function __construct()
    {
        set_error_handler(
            function (int $type, string $message, string $srcFile, int $srcLine): bool {
                throw new ErrorException(
                    message:  ExceptionUtil::buildMessage($message, ['error type' => $type, 'source code location' => $srcFile . ':' . $srcLine]),
                    code:     0,
                    severity: $type,
                    filename: $srcFile,
                    line:     $srcLine
                );
            }
        );

        $this->logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('this'));

        parent::__construct();

        ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__)) && $loggerProxy->log('Done');
    }

    #[Override]
    protected function processConfig(): void
    {
        parent::processConfig();

        Assert::assertCount(
            $this->expectedPortsCount(),
            AmbientContextForTests::testConfig()->dataPerProcess()->thisServerPorts,
            LoggableToString::convert(AmbientContextForTests::testConfig())
        );

        // At this point request is not parsed and applied to config yet
        TestCase::assertNull(AmbientContextForTests::testConfig()->getOptionValueByName(OptionForTestsName::data_per_request));
    }

    /**
     * @return int
     */
    protected function expectedPortsCount(): int
    {
        return 1;
    }

    protected function onNewConnection(int $socketIndex, ConnectionInterface $connection): void
    {
        ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
        && $loggerProxy->log(
            'New connection',
            [
                'socketIndex' => $socketIndex,
                'connection addresses' => [
                    'remote' => $connection->getRemoteAddress(),
                    'local'  => $connection->getLocalAddress(),
                ]
            ]
        );
    }

    /**
     * @return null|ResponseInterface|Promise<ResponseInterface>
     */
    abstract protected function processRequest(ServerRequestInterface $request): null|ResponseInterface|Promise;

    public static function run(): void
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        self::runSkeleton(
            function (SpawnedProcessBase $thisObj): void {
                /** @var self $thisObj */
                $thisObj->runImpl();
            }
        );
    }

    public function runImpl(): void
    {
        $this->runHttpService();
    }

    private function runHttpService(): void
    {
        $loggerProxyDebug = $this->logger->ifDebugLevelEnabledNoLine(__FUNCTION__);

        $ports = AmbientContextForTests::testConfig()->dataPerProcess()->thisServerPorts;
        $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Running HTTP service...', compact('ports'));

        $this->reactLoop = Loop::get();
        TestCase::assertNotEmpty($ports);
        foreach ($ports as $port) {
            $uri = HttpServerHandle::SERVER_LOCALHOST_ADDRESS . ':' . $port;
            $serverSocket = new SocketServer($uri, /* context */ [], $this->reactLoop);
            $socketIndex = count($this->serverSockets);
            $this->serverSockets[] = $serverSocket;
            $serverSocket->on(
                'connection' /* <- event */,
                function (ConnectionInterface $connection) use ($socketIndex): void {
                    $this->onNewConnection($socketIndex, $connection);
                }
            );
            $httpServer = new HttpServer(
                /**
                 * @return ResponseInterface|Promise<ResponseInterface>
                 */
                function (ServerRequestInterface $request): ResponseInterface|Promise {
                    return $this->processRequestWrapper($request);
                }
            );
            $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Listening for incoming requests...', ['serverSocket address' => $serverSocket->getAddress()]);
            $httpServer->listen($serverSocket);
        }

        $this->beforeLoopRun();

        Assert::assertNotNull($this->reactLoop);
        $this->reactLoop->run();
    }

    protected function beforeLoopRun(): void
    {
    }

    protected function shouldRequestHaveSpawnedProcessInternalId(ServerRequestInterface $request): bool
    {
        return true;
    }

    /**
     * @return ResponseInterface|Promise<ResponseInterface>
     */
    private function processRequestWrapper(ServerRequestInterface $request): Promise|ResponseInterface
    {
        $loggerProxyDebug = $this->logger->ifDebugLevelEnabledNoLine(__FUNCTION__);
        $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Received request', ['URI' => $request->getUri(), 'method' => $request->getMethod(), 'target' => $request->getRequestTarget()]);

        try {
            $response = $this->processRequestWrapperImpl($request);

            if ($response instanceof ResponseInterface) {
                $loggerProxyDebug && $loggerProxyDebug->log(
                    __LINE__,
                    'Sending response ...',
                    ['statusCode' => $response->getStatusCode(), 'reasonPhrase' => $response->getReasonPhrase(), 'body' => $response->getBody()]
                );
            } else {
                Assert::assertInstanceOf(Promise::class, $response); // @phpstan-ignore staticMethod.alreadyNarrowedType
                $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Promise returned - response will be returned later...');
            }

            return $response;
        } catch (Throwable $throwable) {
            ($loggerProxy = $this->logger->ifCriticalLevelEnabled(__LINE__, __FUNCTION__))
            && $loggerProxy->log('processRequest() exited by exception - terminating this process', compact('throwable'));
            exit(self::FAILURE_PROCESS_EXIT_CODE);
        }
    }

    /**
     * @return ResponseInterface|Promise<ResponseInterface>
     */
    private function processRequestWrapperImpl(ServerRequestInterface $request): Promise|ResponseInterface
    {
        if ($this->shouldRequestHaveSpawnedProcessInternalId($request)) {
            $testConfigForRequest = ConfigUtilForTests::read(
                new RequestHeadersRawSnapshotSource(
                    function (string $headerName) use ($request): ?string {
                        return self::getRequestHeader($request, $headerName);
                    }
                ),
                AmbientContextForTests::loggerFactory()
            );

            $verifySpawnedProcessInternalIdResponse = self::verifySpawnedProcessInternalId(
                $testConfigForRequest->dataPerRequest()->spawnedProcessInternalId
            );
            if ($verifySpawnedProcessInternalIdResponse !== null) {
                return $verifySpawnedProcessInternalIdResponse;
            }
        }

        switch ($request->getUri()->getPath()) {
            case HttpServerHandle::STATUS_CHECK_URI_PATH:
                return self::buildResponseWithPid();
            case self::RESET_LOG_LEVEL_URI_PATH:
                return self::resetLogLevel($request);
            case self::EXIT_URI_PATH:
                $this->exit();
                return self::buildDefaultResponse();
        }

        if (($response = $this->processRequest($request)) !== null) {
            return $response;
        }

        return self::buildErrorResponse(
            HttpStatusCodes::BAD_REQUEST,
            'Unknown URI path: `' . $request->getRequestTarget() . '\''
        );
    }

    private function resetLogLevel(ServerRequestInterface $request): ResponseInterface
    {
        $newLogLevel = AssertEx::notNull(LogLevel::tryToFindByName(self::getRequiredRequestHeader($request, self::LOG_LEVEL_HEADER_NAME)));
        AmbientContextForTests::resetLogLevel($newLogLevel);
        return self::buildDefaultResponse();
    }

    protected function exit(): void
    {
        foreach ($this->serverSockets as $serverSocket) {
            $serverSocket->close();
        }

        ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
        && $loggerProxy->log('Exiting...');
    }

    protected static function getRequestHeader(ServerRequestInterface $request, string $headerName): ?string
    {
        $headerValues = $request->getHeader($headerName);
        if (ArrayUtilForTests::isEmpty($headerValues)) {
            return null;
        }
        if (count($headerValues) !== 1) {
            throw new ComponentTestsInfraException(ExceptionUtil::buildMessage('The header should not have more than one value', compact('headerName', 'headerValues')));
        }
        return $headerValues[0];
    }

    protected static function getRequiredRequestHeader(ServerRequestInterface $request, string $headerName): string
    {
        $headerValue = self::getRequestHeader($request, $headerName);
        if ($headerValue === null) {
            throw new ComponentTestsInfraException(ExceptionUtil::buildMessage('Missing required HTTP request header', compact('headerName')));
        }
        return $headerValue;
    }
}
