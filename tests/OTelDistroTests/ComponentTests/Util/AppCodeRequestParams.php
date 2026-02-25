<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use OTelDistroTests\Util\MixedMap;

class AppCodeRequestParams implements LoggableInterface
{
    use LoggableTrait;

    public TestInfraDataPerRequest $dataPerRequest;

    public function __construct(string $spawnedProcessInternalId, AppCodeTarget $appCodeTarget)
    {
        $this->dataPerRequest = new TestInfraDataPerRequest(spawnedProcessInternalId: $spawnedProcessInternalId, appCodeTarget: $appCodeTarget);
    }

    /**
     * @param MixedMap|array<string, mixed> $appCodeArgs
     */
    public function setAppCodeArgs(MixedMap|array $appCodeArgs): void
    {
        $this->dataPerRequest->appCodeArguments = $appCodeArgs instanceof MixedMap ? $appCodeArgs->cloneAsArray() : $appCodeArgs;
    }

    /** @noinspection PhpUnused */
    public function setIsAppCodeExpectedToThrow(bool $isAppCodeExpectedToThrow): void
    {
        $this->dataPerRequest->isAppCodeExpectedToThrow = $isAppCodeExpectedToThrow;
    }
}
