<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\ExceptionUtil;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\Log\Logger;

final class ProcessHandle
{
    /** @var ?resource $procOpenRetVal */
    private mixed $procOpenRetVal;
    private ProcessInfo $cachedInfo;
    private readonly Logger $logger;

    /**
     * @param resource $procOpenRetVal
     */
    public function __construct(
        private readonly string $dbgProcessName,
        mixed $procOpenRetVal,
    ) {
        $this->procOpenRetVal = $procOpenRetVal;
        $this->logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('this'));
    }

    public function getCurrentInfo(): ProcessInfo
    {
        return $this->refresh();
    }

    private function refresh(): ProcessInfo
    {
        if (!$this->cachedInfo->hasExited()) {
            $procStatus = proc_get_status(AssertEx::notNull($this->procOpenRetVal));
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if (!is_array($procStatus)) { // @phpstan-ignore function.alreadyNarrowedType
                throw new ComponentTestsInfraException(ExceptionUtil::buildMessage('proc_get_status returned value which means an error', compact('procStatus')));
            }

            $pid = AssertEx::isInt($procStatus['pid']);
            $exitCode = AssertEx::isBool($procStatus['running']) ? null : AssertEx::isInt($procStatus['exitcode']);
            $this->cachedInfo = new ProcessInfo($pid, $exitCode);
        }

        return $this->cachedInfo;
    }

    public function waitForProcessToExit(int $maxWaitTimeInMicroseconds): bool
    {
        $logDebug = $this->logger->inherit()->addAllContext(compact('maxWaitTimeInMicroseconds'))->ifDebugLevelEnabledNoLine(__FUNCTION__);

        (new PollingCheck($this->dbgProcessName . ' exited', $maxWaitTimeInMicroseconds))->run(fn() => $this->refresh()->hasExited());

        if ($this->cachedInfo->hasExited()) {
            $logDebug?->log(__LINE__, 'Process exited');
        } else {
            $this->logger->ifWarningLevelEnabled(__LINE__, __FUNCTION__)?->log('Wait for the started process to exit timed out');
        }

        return $this->cachedInfo->hasExited();
    }

    public function close(): void
    {
        $procCloseRetVal = proc_close(AssertEx::notNull($this->procOpenRetVal));
        $this->procOpenRetVal = null;
        // For older versions of PHP (prior to 8.3.0), calling proc_get_status() after the process had already exited
        // would cause subsequent calls to proc_get_status() or proc_close() to return -1.
        // PHP 8.3.0 and newer: This behavior was corrected.
        // The process's exit code is now cached, and subsequent calls will return the correct, cached value.
        if (PHP_VERSION_ID >= 80300 && $procCloseRetVal === -1) {
            throw new ComponentTestsInfraException(ExceptionUtil::buildMessage('proc_close returned value which means an error', $this->logger->getContext()));
        }
    }
}
