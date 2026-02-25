<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util\OtlpData;

use OTelDistroTests\Util\AssertEx;
use Opentelemetry\Proto\Common\V1\InstrumentationScope as OTelProtoInstrumentationScope;

/**
 * @see https://github.com/open-telemetry/opentelemetry-proto/blob/v1.8.0/opentelemetry/proto/common/v1/common.proto#L71
 *
 * @param non-negative-int $droppedAttributesCount
 */
class InstrumentationScope
{
    /**
     * @param non-negative-int $droppedAttributesCount
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly Attributes $attributes,
        public readonly int $droppedAttributesCount,
    ) {
    }

    public static function deserializeFromOTelProto(OTelProtoInstrumentationScope $source): self
    {
        return new self(
            name: $source->getName(),
            version: $source->getVersion(),
            attributes: Attributes::deserializeFromOTelProto($source->getAttributes()),
            droppedAttributesCount: AssertEx::isNonNegativeInt($source->getDroppedAttributesCount()),
        );
    }
}
