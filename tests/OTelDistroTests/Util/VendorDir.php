<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

final class VendorDir
{
    use StaticClassTrait;
    use RootDirTrait;

    public static function getFullPath(): string
    {
        if (self::$fullPath === null) {
            self::$fullPath = RepoRootDir::adaptRelativeUnixStylePath('vendor');
        }
        return self::$fullPath;
    }
}
