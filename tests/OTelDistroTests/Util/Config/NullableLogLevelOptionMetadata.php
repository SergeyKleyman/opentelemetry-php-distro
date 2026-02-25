<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Log\LogLevel;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends NullableOptionMetadata<LogLevel>
 *
 * @noinspection PhpUnused
 */
final class NullableLogLevelOptionMetadata extends NullableOptionMetadata
{
    public function __construct()
    {
        parent::__construct(LogLevelOptionMetadata::parserSingleton());
    }
}
