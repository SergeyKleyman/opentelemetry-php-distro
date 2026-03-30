<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OTelDistroTools\Build;

use OpenTelemetry\Distro\Util\EnumUtilTrait;

enum PhpDepsGroup
{
    use EnumUtilTrait;

    case dev;
    case prod;
    case dev_for_prod_static_check;
}
