<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\Optional;
use Override;
use PHPUnit\Framework\Assert;

final class NullableStringExpectations implements ExpectationsInterface
{
    use ExpectationsTrait;

    /**
     * @param Optional<?string> $expectedValue
     * @param bool              $isExpectedValueRegex
     */
    private function __construct(
        public readonly Optional $expectedValue,
        public readonly bool $isExpectedValueRegex = false,
    ) {
    }

    public static function regex(string $expectedValueRegex): self
    {
        return new self(Optional::value($expectedValueRegex), isExpectedValueRegex: true); // @phpstan-ignore argument.type
    }

    public static function literal(?string $expectedValue): self
    {
        return new self(Optional::value($expectedValue)); // @phpstan-ignore argument.type
    }

    public static function matchAny(): self
    {
        /** @var ?self $cached */
        static $cached = null;
        return $cached ??= new self(Optional::none()); // @phpstan-ignore argument.type
    }

    public function assertMatches(?string $actual): void
    {
        if (!$this->expectedValue->isValueSet()) {
            return;
        }

        if ($this->isExpectedValueRegex) {
            Assert::assertMatchesRegularExpression(AssertEx::notNull($this->expectedValue->getValue()), AssertEx::notNull($actual));
        } else {
            Assert::assertSame($this->expectedValue->getValue(), $actual);
        }
    }

    #[Override]
    public function assertMatchesMixed(mixed $actual): void
    {
        $this->assertMatches(AssertEx::isNullableString($actual));
    }
}
