<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\StaticClassTrait;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class LogCategoryForTests
{
    use StaticClassTrait;

    public const CONFIG = 'config';
    public const TEST_INFRA = 'test-infra';
    public const TEST = 'test';
}
