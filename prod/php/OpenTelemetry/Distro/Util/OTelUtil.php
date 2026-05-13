<?php

declare(strict_types=1);

namespace OpenTelemetry\Distro\Util;

final class OTelUtil
{
    use StaticClassTrait;

    public static function buildFqFunctionName(?string $fqClassName, string $shortFunctionName): string
    {
        return empty($fqClassName) ? $shortFunctionName : ($fqClassName . '::' . $shortFunctionName);
    }
}
