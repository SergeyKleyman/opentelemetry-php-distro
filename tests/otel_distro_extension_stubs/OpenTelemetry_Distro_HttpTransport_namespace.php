<?php

declare(strict_types=1);

namespace OpenTelemetry\Distro\HttpTransport;

/**
 * This function is implemented by the extension
 *
 * @param array<string,string|string[]> $headers
 */
function initialize(
    string $endpoint,
    string $contentType,
    array $headers,
    float $timeout,
    int $retryDelay,
    int $maxRetries,
): void {
}

/**
 * This function is implemented by the extension
 */
function enqueue(string $endpoint, string $payload): void
{
}
