<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;

final class TestsRootDir
{
    use StaticClassTrait;
    use RootDirTrait;

    public static function getFullPath(): string
    {
        if (self::$fullPath === null) {
            self::$fullPath = RepoRootDir::adaptRelativeUnixStylePath('tests');
        }
        return self::$fullPath;
    }
}
