<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\EnumUtilTrait;
use PHPUnit\Framework\Assert;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
trait EnumUtilForTestsTrait
{
    use EnumUtilTrait;

    public static function findByName(string $enumName): self
    {
        $result = self::tryToFindByName($enumName);
        Assert::assertNotNull($result);
        return $result;
    }
}
