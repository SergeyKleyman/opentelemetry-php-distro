<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util\OtlpData;

use Opentelemetry\Proto\Trace\V1\ResourceSpans as OTelProtoResourceSpans;

/**
 * @see https://github.com/open-telemetry/opentelemetry-proto/blob/v1.8.0/opentelemetry/proto/trace/v1/trace.proto#L48
 */
class ResourceSpans
{
    /**
     * @param ScopeSpans[] $scopeSpans
     *
     * This schema_url applies to the data in the "resource" field.
     * It does not apply to the data in the "scope_spans" field which have their own schema_url field.
     */
    public function __construct(
        public readonly ?OTelResource $resource,
        public readonly array $scopeSpans,
        public readonly string $schemaUrl,
    ) {
    }

    public static function deserializeFromOTelProto(OTelProtoResourceSpans $source): self
    {
        return new self(
            resource: DeserializationUtil::deserializeNullableFromOTelProto($source->getResource(), OTelResource::deserializeFromOTelProto(...)),
            scopeSpans: DeserializationUtil::deserializeArrayFromOTelProto($source->getScopeSpans(), ScopeSpans::deserializeFromOTelProto(...)),
            schemaUrl: $source->getSchemaUrl(),
        );
    }

    /**
     * @return iterable<Span>
     */
    public function spans(): iterable
    {
        foreach ($this->scopeSpans as $scopeSpans) {
            yield from $scopeSpans->spans;
        }
    }
}
