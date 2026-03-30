<?php

declare(strict_types=1);

namespace OpenTelemetry\Contrib\Otlp;

use OpenTelemetry\API\Behavior\Internal\Logging;
use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\API\Trace\SpanKind as ApiTraceSpanKind;
use Opentelemetry\Proto\Collector\Trace\V1\ExportTraceServiceResponse;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LogLevel;
use Throwable;

use function OpenTelemetry\Distro\OtlpExporters\convert_spans;

/**
 * @psalm-import-type SUPPORTED_CONTENT_TYPES from ProtobufSerializer
 */
final class SpanExporter implements SpanExporterInterface
{
    use LogsMessagesTrait;

    /**
     * @psalm-param TransportInterface<SUPPORTED_CONTENT_TYPES> $transport
     */
    public function __construct(
        private readonly TransportInterface $transport
    ) {
    }

    /** @inheritDoc */
    public function export(iterable $batch, ?CancellationInterface $cancellation = null): FutureInterface
    {
        return $this->transport
            ->send(self::convertSpans($batch), $cancellation)
            ->map(
                static function (mixed $payload): bool {
                    if ($payload === null) {
                        return true;
                    }

                    $serviceResponse = new ExportTraceServiceResponse();
                    $partialSuccess = $serviceResponse->getPartialSuccess();
                    if ($partialSuccess !== null && $partialSuccess->getRejectedSpans()) {
                        self::logError('Export partial success', [
                            'rejected_spans' => $partialSuccess->getRejectedSpans(),
                            'error_message' => $partialSuccess->getErrorMessage(),
                        ]);

                        return false;
                    }
                    if ($partialSuccess !== null && $partialSuccess->getErrorMessage()) {
                        self::logWarning('Export success with warnings/suggestions', ['error_message' => $partialSuccess->getErrorMessage()]);
                    }

                    return true;
                }
            )->catch(
                static function (Throwable $throwable): bool {
                    self::logError('Export failure', ['exception' => $throwable]);

                    return false;
                }
            );
    }

    /**
     * This function is implemented by the extension
     *
     * @param iterable<SpanDataInterface> $batch
     */
    private static function convertSpans(iterable $batch): string
    {
        $batchCopy = $batch;
        if (Logging::level(LogLevel::DEBUG) >= Logging::logLevel()) {
            self::logDebug('Calling native convert_spans');
            $batchCopy = [];
            foreach ($batch as $span) {
                $batchCopy[] = $span;
                self::logDebug(
                    'Span #' . count($batchCopy) . ' in the batch'
                    . '; name: ' . $span->getName()
                    . ', kind: ' . self::dbgApiTraceSpanKindToString($span->getKind()) . ' (as int: ' . $span->getKind() . ')'
                    . ', span ID: ' . $span->getSpanId()
                    . ', trace ID: ' . $span->getTraceId()
                    . ', parent span ID: ' . $span->getParentSpanId()
                    . ', status: {code: ' . $span->getStatus()->getCode() . ', description: `' . $span->getStatus()->getDescription() . "'}"
                    . ', attributes: ' . json_encode($span->getAttributes()->toArray())
                    . ($span->getAttributes()->getDroppedAttributesCount() === 0 ? '' : (', dropped attributes count: ' . $span->getAttributes()->getDroppedAttributesCount()))
                );
            }
        }

        $payload = convert_spans($batchCopy);
        self::logDebug('Result returned by native convert_spans', ['$payload size' => strlen(bin2hex($payload)) / 2]);
        return $payload;
    }

    private static function dbgApiTraceSpanKindToString(int $spanKind): string
    {
        // TODO: Sergey Kleyman: Add unit test
        return match ($spanKind) {
            ApiTraceSpanKind::KIND_INTERNAL => 'internal',
            ApiTraceSpanKind::KIND_CLIENT => 'client',
            ApiTraceSpanKind::KIND_SERVER => 'server',
            ApiTraceSpanKind::KIND_PRODUCER => 'producer',
            ApiTraceSpanKind::KIND_CONSUMER => 'consumer',
            default => "UNKNOWN ($spanKind as int)",
        };
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return $this->transport->shutdown($cancellation);
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return $this->transport->forceFlush($cancellation);
    }
}
