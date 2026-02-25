<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use PHPUnit\Framework\Assert;

final class RepoRootDir
{
    use StaticClassTrait;
    use RootDirTrait;

    public static function setFullPath(string $fullPath): void
    {
        Assert::assertNull(self::$fullPath);
        self::$fullPath = FileUtil::normalizePath($fullPath);
    }

    public static function getFullPath(): string
    {
        Assert::assertNotNull(self::$fullPath);
        return self::$fullPath;
    }
}
