<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util\OtlpData;

use OpenTelemetry\Distro\Util\ArrayUtil;
use OTelDistroTests\Util\EnumUtilForTestsTrait;
use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use Opentelemetry\Proto\Trace\V1\Span\SpanKind as OTelProtoSpanKind;
use PHPUnit\Framework\Assert;

enum SpanKind implements LoggableInterface
{
    use EnumUtilForTestsTrait;

    case unspecified;
    case internal;
    case client;
    case server;
    case producer;
    case consumer;

    private const FROM_OTEL_PROTO_SPAN_KIND = [
        OTelProtoSpanKind::SPAN_KIND_UNSPECIFIED => self::unspecified,
        OTelProtoSpanKind::SPAN_KIND_INTERNAL => self::internal,
        OTelProtoSpanKind::SPAN_KIND_CLIENT => self::client,
        OTelProtoSpanKind::SPAN_KIND_SERVER => self::server,
        OTelProtoSpanKind::SPAN_KIND_PRODUCER => self::producer,
        OTelProtoSpanKind::SPAN_KIND_CONSUMER => self::consumer,
    ];

    public static function fromOTelProtoSpanKind(int $otelProtoSpanKind): self
    {
        if (ArrayUtil::getValueIfKeyExists($otelProtoSpanKind, self::FROM_OTEL_PROTO_SPAN_KIND, /* out */ $result)) {
            return $result;
        }
        Assert::fail('Unexpected span kind: ' . $otelProtoSpanKind);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs($this->name);
    }
}
