<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util\OtlpData;

use OTelDistroTests\Util\AssertEx;
use Opentelemetry\Proto\Resource\V1\Resource as OTelProtoResource;

/**
 * @see https://github.com/open-telemetry/opentelemetry-proto/blob/v1.8.0/opentelemetry/proto/resource/v1/resource.proto#L28
 */
class OTelResource
{
    /**
     * @param non-negative-int $droppedAttributesCount
     */
    public function __construct(
        public readonly Attributes $attributes,
        public readonly int $droppedAttributesCount,
    ) {
    }

    public static function deserializeFromOTelProto(OTelProtoResource $source): self
    {
        return new self(
            attributes: Attributes::deserializeFromOTelProto($source->getAttributes()),
            droppedAttributesCount: AssertEx::isNonNegativeInt($source->getDroppedAttributesCount()),
        );
    }
}
