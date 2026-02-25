<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\AmbientContextForTests;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\Log\LogCategoryForTests;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span as OTelApiSpan;
use OpenTelemetry\API\Trace\SpanInterface as OTelApiSpanInterface;
use OpenTelemetry\API\Trace\SpanKind as OTelSpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use OpenTelemetry\SemConv\Version;
use PHPUnit\Framework\Assert;
use Throwable;

/**
 * @phpstan-type OTelAttributeScalarValue bool|int|float|string|null
 * @phpstan-type OTelAttributeValue OTelAttributeScalarValue|array<OTelAttributeScalarValue>
 * @phpstan-type OTelAttributesMapIterable iterable<non-empty-string, OTelAttributeValue>
 * @phpstan-type IntLimitedToOTelSpanKind OTelSpanKind::KIND_*
 */
class OTelUtil
{
    public static function getTracer(): TracerInterface
    {
        return Globals::tracerProvider()->getTracer(name: 'org.opentelemetry.php.distro.component-tests', version: Version::VERSION_1_27_0->value);
    }

    /**
     * @phpstan-param non-empty-string          $spanName
     * @phpstan-param IntLimitedToOTelSpanKind  $spanKind
     * @phpstan-param OTelAttributesMapIterable $attributes
     */
    public static function startSpan(TracerInterface $tracer, string $spanName, int $spanKind = OTelSpanKind::KIND_INTERNAL, iterable $attributes = []): OTelApiSpanInterface
    {
        $parentCtx = Context::getCurrent();
        $newSpanBuilder = $tracer->spanBuilder($spanName)->setParent($parentCtx)->setSpanKind($spanKind)->setAttributes($attributes);
        $newSpan = $newSpanBuilder->startSpan();
        $newSpanCtx = $newSpan->storeInContext($parentCtx);
        Context::storage()->attach($newSpanCtx);
        return $newSpan;
    }

    /**
     * @param OTelAttributesMapIterable $attributes
     */
    public static function endActiveSpan(?Throwable $throwable = null, ?string $errorStatus = null, iterable $attributes = []): void
    {
        $scope = Context::storage()->scope();
        if ($scope === null) {
            return;
        }
        Assert::assertSame(0, $scope->detach());
        $span = OTelApiSpan::fromContext($scope->context());

        $span->setAttributes($attributes);

        if ($errorStatus !== null) {
            $span->setAttribute(TraceAttributes::EXCEPTION_MESSAGE, $errorStatus);
            $span->setStatus(StatusCode::STATUS_ERROR, $errorStatus);
        }

        if ($throwable) {
            $span->recordException($throwable);
            $span->setStatus(StatusCode::STATUS_ERROR, $throwable->getMessage());
        }

        $span->end();
    }

    /**
     * @phpstan-param non-empty-string          $spanName
     * @phpstan-param IntLimitedToOTelSpanKind  $spanKind
     * @phpstan-param OTelAttributesMapIterable $attributes
     */
    public static function startEndSpan(
        TracerInterface $tracer,
        string $spanName,
        int $spanKind = OTelSpanKind::KIND_INTERNAL,
        iterable $attributes = [],
        ?Throwable $throwable = null,
        ?string $errorStatus = null
    ): void {
        self::startSpan($tracer, $spanName, $spanKind, $attributes);
        self::endActiveSpan($throwable, $errorStatus);
    }

    /**
     * @param iterable<string> $attributeKeys
     *
     * @return array<string, mixed>
     */
    public static function dbgDescForSpan(OTelApiSpanInterface $span, iterable $attributeKeys = []): array
    {
        $result = ['class' => get_class($span), 'isRecording' => $span->isRecording()];
        if (method_exists($span, 'getName')) {
            $result['name'] = $span->getName();
        }
        if (method_exists($span, 'getAttribute')) {
            $attributes = [];
            foreach ($attributeKeys as $attributeKey) {
                $attributes[$attributeKey] = $span->getAttribute($attributeKey);
            }
            $result['attributes'] = $attributes;
        }
        return $result;
    }

    /**
     * @param OTelAttributesMapIterable $attributes
     */
    public static function addSpanAttributes(OTelApiSpanInterface $span, iterable $attributes): void
    {
        $logger = AmbientContextForTests::loggerFactory()->loggerForClass(LogCategoryForTests::TEST_INFRA, __NAMESPACE__, __CLASS__, __FILE__);
        $loggerProxyDebug = $logger->ifDebugLevelEnabledNoLine(__FUNCTION__);
        $logger->addAllContext(compact('attributes'));

        $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'Before setting attributes', ['span' => self::dbgDescForSpan($span, IterableUtil::keys($attributes))]);
        $span->setAttributes($attributes);
        $loggerProxyDebug && $loggerProxyDebug->log(__LINE__, 'After setting attributes', ['span' => self::dbgDescForSpan($span, IterableUtil::keys($attributes))]);
    }

    /**
     * @param OTelAttributesMapIterable $attributes
     */
    public static function addActiveSpanAttributes(iterable $attributes): void
    {
        self::addSpanAttributes(OTelApiSpan::getCurrent(), $attributes);
    }
}
