<?php

/** @noinspection PhpUnusedParameterInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro;

use Closure;
use Throwable;

/**
 * This function is implemented by the extension
 *
 * @return ?array<array-key, mixed>
 */
function get_remote_configuration(): ?array // @phpstan-ignore return.unusedType
{
    return ['dummy file name' => 'dummy file content (JSON)'];
}

/**
 * This function is implemented by the extension
 *
 * @noinspection PhpUnusedParameterInspection
 */
function log_feature(
    int $isForced,
    int $level,
    int $feature,
    string $file,
    ?int $line,
    string $func,
    string $message
): void {
}

/**
 * This function is implemented by the extension
 *
 * @noinspection PhpUnusedParameterInspection
 */
function get_config_option_by_name(string $optionName): mixed
{
    return null;
}

/**
 * This function is implemented by the extension
 *
 * @phpstan-param ?string $class The hooked function's class. Null for a global/built-in function.
 * @phpstan-param string $function The hooked function's name.
 * @phpstan-param ?(Closure(?object $thisObj, array<mixed> $params, string $class, string $function, ?string $filename, ?int $lineno): (void|array<mixed>)) $pre
 *                  return value is modified parameters
 * @phpstan-param ?(Closure(?object $thisObj, array<mixed> $params, mixed $returnValue, ?Throwable $throwable): mixed) $post
 *                  return value is modified return value
 *
 * @return bool Whether the observer was successfully added
 *
 * @see https://github.com/open-telemetry/opentelemetry-php-instrumentation
 *
 * @noinspection PhpUnusedParameterInspection
 */
function hook(?string $class, string $function, ?Closure $pre, ?Closure $post): bool
{
    return false;
}

/**
 * This function is implemented by the extension
 */
function is_enabled(): bool
{
    return false;
}
