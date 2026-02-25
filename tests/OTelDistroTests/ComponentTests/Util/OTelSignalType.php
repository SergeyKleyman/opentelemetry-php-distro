<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\EnumUtilForTestsTrait;

enum OTelSignalType
{
    use EnumUtilForTestsTrait;

    case trace;
    case metric;
    case log;
    case baggage;
}
