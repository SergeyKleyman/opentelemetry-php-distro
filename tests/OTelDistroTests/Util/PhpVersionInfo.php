<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use PHPUnit\Framework\Assert;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class PhpVersionInfo
{
    use ComparableTrait;

    private function __construct(
        private readonly int $major,
        private readonly int $minor,
    ) {
    }

    public static function fromMajorMinorNoDotString(string $majorMinorNoDotString): self
    {
        Assert::assertSame(2, strlen($majorMinorNoDotString));
        $major = substr($majorMinorNoDotString, offset:  0, length: 1);
        $minor = substr($majorMinorNoDotString, offset:  1, length: 1);
        return new self(AssertEx::stringIsInt($major), AssertEx::stringIsInt($minor));
    }

    public static function fromMajorDotMinor(string $majorDotMinor): self
    {
        $versionParts = explode('.', $majorDotMinor);
        Assert::assertCount(2, $versionParts);
        return new self(AssertEx::stringIsInt($versionParts[0]), AssertEx::stringIsInt($versionParts[1]));
    }

    /**
     * @return int[]
     */
    private function asParts(): array
    {
        return [$this->major, $this->minor];
    }

    public function compare(self $other): int
    {
        return NumericUtilForTests::compareSequences($this->asParts(), $other->asParts());
    }

    public function asDotSeparated(): string
    {
        return $this->major . '.' . $this->minor;
    }
}
