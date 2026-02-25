<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Closure;
use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OTelDistroTests\Util\Log\Logger;
use OTelDistroTests\Util\TimeUtil;

final class PollingCheck
{
    private readonly Logger $logger;
    private string $dbgDesc;
    private int $maxWaitTimeInMicroseconds;
    private int $sleepTimeInMicroseconds = 1000 * 1000; // 1 second
    private int $reportIntervalInMicroseconds = 1000 * 1000; // 1 second

    public function __construct(string $dbgDesc, int $maxWaitTimeInMicroseconds)
    {
        $this->dbgDesc = $dbgDesc;
        $this->maxWaitTimeInMicroseconds = $maxWaitTimeInMicroseconds;
        $this->logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__);
    }

    /**
     * @param Closure(): bool $check
     */
    public function run(Closure $check): bool
    {
        ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
        && $loggerProxy->log(
            'Starting to check if ' . $this->dbgDesc . '...',
            ['maxWaitTime' => TimeUtil::formatDurationInMicroseconds($this->maxWaitTimeInMicroseconds)]
        );

        $numberOfAttempts = 0;
        $sinceStarted = new Stopwatch();
        $sinceLastReport = new Stopwatch();
        while (true) {
            ++$numberOfAttempts;
            ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
            && $loggerProxy->log('Starting attempt ' . $numberOfAttempts . ' to check if ' . $this->dbgDesc . '...');
            /** @noinspection PhpIfWithCommonPartsInspection */
            if ($check()) {
                $elapsedTime = $sinceStarted->elapsedInMicroseconds();
                ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
                && $loggerProxy->log(
                    'Successfully completed checking if ' . $this->dbgDesc,
                    ['elapsedTime' => TimeUtil::formatDurationInMicroseconds($elapsedTime)]
                );
                return true;
            }

            $elapsedTime = $sinceStarted->elapsedInMicroseconds();
            if ($elapsedTime >= $this->maxWaitTimeInMicroseconds) {
                break;
            }

            if ($sinceLastReport->elapsedInMicroseconds() >= $this->reportIntervalInMicroseconds) {
                ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
                && $loggerProxy->log(
                    'Still checking if ' . $this->dbgDesc . '...',
                    [
                        'elapsedTime'      => TimeUtil::formatDurationInMicroseconds($elapsedTime),
                        'numberOfAttempts' => $numberOfAttempts,
                        'maxWaitTime'      => TimeUtil::formatDurationInMicroseconds($this->maxWaitTimeInMicroseconds),
                    ]
                );
                $sinceLastReport->restart();
            }

            ($loggerProxy = $this->logger->ifTraceLevelEnabled(__LINE__, __FUNCTION__))
            && $loggerProxy->log(
                'Sleeping ' . TimeUtil::formatDurationInMicroseconds($this->sleepTimeInMicroseconds)
                . ' before checking again if ' . $this->dbgDesc
                . ' (numberOfAttempts: ' . $numberOfAttempts . ')'
                . '...'
            );
            usleep($this->sleepTimeInMicroseconds);
        }

        ($loggerProxy = $this->logger->ifDebugLevelEnabled(__LINE__, __FUNCTION__))
        && $loggerProxy->log(
            'Reached max wait time while checking if ' . $this->dbgDesc,
            [
                'elapsedTime'      => TimeUtil::formatDurationInMicroseconds($sinceStarted->elapsedInMicroseconds()),
                'numberOfAttempts' => $numberOfAttempts,
                'maxWaitTime'      => TimeUtil::formatDurationInMicroseconds($this->maxWaitTimeInMicroseconds),
            ]
        );
        return false;
    }
}
