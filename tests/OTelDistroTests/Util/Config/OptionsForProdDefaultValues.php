<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class OptionsForProdDefaultValues
{
    use StaticClassTrait;

    public const LOG_LEVEL_FILE = LogLevel::off;
    public const LOG_LEVEL_STDERR = LogLevel::off;
    public const LOG_LEVEL_SYSLOG = LogLevel::info;

    public const TRANSACTION_SPAN_ENABLED = true;
    public const TRANSACTION_SPAN_ENABLED_CLI = true;
}
