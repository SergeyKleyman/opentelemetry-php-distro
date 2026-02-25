<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests;

use OpenTelemetry\Distro\InferredSpans\InferredSpans;
use OTelDistroTests\ComponentTests\Util\InferredSpanExpectationsBuilder;
use OTelDistroTests\Util\TestCaseBase;

final class InferredSpansUnitTest extends TestCaseBase
{
    public function testInferredAttributeName(): void
    {
        self::assertSame(InferredSpans::IS_INFERRED_ATTRIBUTE_NAME, InferredSpanExpectationsBuilder::IS_INFERRED_ATTRIBUTE_NAME); // @phpstan-ignore staticMethod.alreadyNarrowedType
    }
}
