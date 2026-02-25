<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

declare(strict_types=1);

namespace OTelDistroTests\Util;

use Override;

trait DisableDebugContextTestTrait
{
    #[Override]
    protected function shouldDebugContextBeEnabledForThisTest(): bool
    {
        return false;
    }
}
