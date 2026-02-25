<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Override;

/**
 * @template T
 */
final class LeafExpectations implements ExpectationsInterface
{
    use ExpectationsTrait;

    /**
     * @param T $expectedValue
     */
    private function __construct(
        public readonly mixed $expectedValue = null,
        public readonly bool $shouldMatchAny = false,
    ) {
    }

    /**
     * @param T $expectedValue
     *
     * @return self<T>
     */
    public static function expectedValue(mixed $expectedValue): self
    {
        return new self($expectedValue);
    }

    /**
     * @return self<mixed>
     */
    public static function matchAny(): self
    {
        /** @var ?self<mixed> $cached */
        static $cached = null;
        return $cached ??= new self(shouldMatchAny: true);
    }

    /**
     * @param T $actual
     */
    public function assertMatches(mixed $actual): void
    {
        $this->assertMatchesMixed($actual);
    }

    #[Override]
    public function assertMatchesMixed(mixed $actual): void
    {
        if ($this->shouldMatchAny) {
            return;
        }

        $this->assertValueMatches($this->expectedValue, $actual);
    }
}
