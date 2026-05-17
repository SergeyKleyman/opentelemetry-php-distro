<?php

declare(strict_types=1);

namespace OpenTelemetry\Distro\Util;

use OpenTelemetry\Distro\PhpPartFacade;

final class BoolUtil
{
    use StaticClassTrait;

    public static function toString(bool $val): string
    {
        return $val ? 'true' : 'false';
    }

    /** @noinspection PhpUnused */
    public static function parseValue(string $boolStringVal): ?bool
    {
        return PhpPartFacade::parseBoolValue($boolStringVal);
    }
}
