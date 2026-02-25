<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\Attributes;
use OpenTelemetry\SemConv\TraceAttributes;
use Override;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-import-type AttributeValue from Attributes
 * @phpstan-type ArrayValue AttributeValue|ExpectationsInterface
 *
 * @extends ArrayExpectations<string, ArrayValue>
 */
final class AttributesArrayExpectations extends ArrayExpectations
{
    /**
     * @phpstan-param string $key
     */
    #[Override]
    protected function assertArrayValueMatches(string|int $key, mixed $expectedValue, mixed $actualValue): void
    {
        if ($key === TraceAttributes::URL_SCHEME) {
            Assert::assertEqualsIgnoringCase($expectedValue, $actualValue);
        } else {
            parent::assertArrayValueMatches($key, $expectedValue, $actualValue); // @phpstan-ignore argument.type
        }
    }
}
