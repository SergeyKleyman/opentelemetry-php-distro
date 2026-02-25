<?php

declare(strict_types=1);

if (!extension_loaded('opentelemetry_distro')) {
    require __DIR__ . '/OpenTelemetry_Distro_namespace.php';
    require __DIR__ . '/OpenTelemetry_Distro_HttpTransport_namespace.php';
    require __DIR__ . '/OpenTelemetry_Distro_InferredSpans_namespace.php';
    require __DIR__ . '/OpenTelemetry_Distro_OtlpExporters_namespace.php';
}
