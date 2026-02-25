<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Util\WildcardListMatcher;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends NullableOptionMetadata<WildcardListMatcher>
 */
final class NullableWildcardListOptionMetadata extends NullableOptionMetadata
{
    public function __construct()
    {
        parent::__construct(new WildcardListOptionParser());
    }
}
