<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\Span;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\TimeUtil;
use Override;
use PHPUnit\Framework\Assert;

final class SpanSequenceExpectations implements ExpectationsInterface
{
    use ExpectationsTrait;

    /**
     * @param SpanExpectations[] $expected
     */
    public function __construct(
        public readonly array $expected
    ) {
    }

    #[Override]
    public function assertMatchesMixed(mixed $actual): void
    {
        AssertEx::isArrayWithValueType(Span::class, $actual);
        /** @var Span[] $actual */
        $this->assertMatches($actual);
    }

    /**
     * @param Span[] $actual
     */
    public function assertMatches(array $actual): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        AssertEx::sameCount($this->expected, $actual);
        $actualSortedByStartTime = self::sortByStartTime($actual);
        $index = 0;
        /** @var ?Span $prevActualSpan */
        $prevActualSpan = null;
        foreach (IterableUtil::zip($this->expected, $actualSortedByStartTime) as [$expectedSpan, $actualSpan]) {
            /** @var SpanExpectations $expectedSpan */
            /** @var Span $actualSpan */
            $dbgCtx->add(compact('index', 'expectedSpan', 'actualSpan'));
            if ($index != 0) {
                Assert::assertNotNull($prevActualSpan);
            }
            $expectedSpan->assertMatches($actualSpan);
            $prevActualSpan = $actualSpan;
            ++$index;
        }
    }

    /**
     * @param Span[] $spans
     *
     * @return Span[]
     */
    private static function sortByStartTime(array $spans): array
    {
        usort(/* in,out */ $spans, fn(Span $span_1, Span $span_2) => TimeUtil::compareTimestamps($span_1->startTimeUnixNano, $span_2->startTimeUnixNano));
        return $spans;
    }
}
