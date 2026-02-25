<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro\OtlpExporters;

use OpenTelemetry\SDK\Logs\ReadableLogRecord;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Metrics\Data\Metric;

/**
 * This function is implemented by the extension
 *
 * @param iterable<SpanDataInterface> $batch
 *
 * @see \OpenTelemetry\SDK\Trace\SpanExporterInterface::export
 */
function convert_spans(iterable $batch): string
{
    return "";
}

/**
 * This function is implemented by the extension
 *
 * @param iterable<ReadableLogRecord> $batch
 *
 * @see \OpenTelemetry\SDK\Logs\LogRecordExporterInterface::export
 */
function convert_logs(iterable $batch): string
{
    return "";
}

/**
 * This function is implemented by the extension
 *
 * @param iterable<int, Metric> $batch
 *
 * @see \OpenTelemetry\SDK\Metrics\MetricExporterInterface::export
 */
function convert_metrics(iterable $batch): string
{
    return "";
}
