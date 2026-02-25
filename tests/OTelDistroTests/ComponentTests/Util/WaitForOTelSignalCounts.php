<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\Span;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use Override;
use PHPUnit\Framework\Assert;

final class WaitForOTelSignalCounts implements IsEnoughAgentBackendCommsInterface, LoggableInterface
{
    use LoggableTrait;

    private int $minSpanCount = 0;
    private int $maxSpanCount = 0;

    private function __construct()
    {
    }

    /**
     * @param positive-int $min
     * @param ?positive-int $max
     */
    public static function spans(int $min, ?int $max = null): self
    {
        Assert::assertGreaterThan(0, $min);
        if ($max !== null) {
            Assert::assertGreaterThanOrEqual($min, $max);
        }

        $result = new WaitForOTelSignalCounts();
        $result->minSpanCount = $min;
        $result->maxSpanCount = $max ?? $min;

        return $result;
    }

    /**
     * @param positive-int $min
     */
    public static function spansAtLeast(int $min): self
    {
        return self::spans(min: $min, max: PHP_INT_MAX);
    }

    #[Override]
    public function reasonNotEnough(AgentBackendComms $comms): ?string
    {
        $spansCount = IterableUtil::count($comms->spans());
        Assert::assertLessThanOrEqual($this->maxSpanCount, $spansCount);

        if ($spansCount < $this->minSpanCount) {
            return "Actual spansCount ($spansCount) < expected minSpanCount ($this->minSpanCount)";
        }

        if (($this->minSpanCount !== 0) && !self::isThereAtLeastOneTraceRootSpan($comms->spans())) {
            return "There is no trace root span among $spansCount accumulated spans";
        }

        return null;
    }

    /**
     * @param iterable<Span> $spans
     */
    private static function isThereAtLeastOneTraceRootSpan(iterable $spans): bool
    {
        return !IterableUtil::isEmpty(IterableUtil::findByPredicateOnValue($spans, fn($span) => !$span->hasParent()));
    }
}
