<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

trait RootDirTrait
{
    private static ?string $fullPath = null;

    public static function adaptRelativeUnixStylePath(string $pathRelativeToRootDir): string
    {
        return FileUtil::normalizePath(self::getFullPath() . DIRECTORY_SEPARATOR . FileUtil::adaptUnixDirectorySeparators($pathRelativeToRootDir));
    }
}
