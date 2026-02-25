<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use PHPUnit\Framework\Assert;

/**
 * @template T
 */
final class Optional implements LoggableInterface
{
    /**
     * @param T $value
     */
    private function __construct(
        public readonly mixed $value,
        public readonly bool $isValueSet = true
    ) {
    }

    /**
     * @param T $value
     *
     * @return self<T>
     */
    public static function value(mixed $value): self
    {
        return new self($value);
    }

    /**
     * @return self<T>
     */
    public static function none(): self
    {
        static $cached = null;
        return $cached ??= new self(value: null, isValueSet: false); // @phpstan-ignore return.type
    }

    /**
     * @return T
     */
    public function getValue(): mixed
    {
        Assert::assertTrue($this->isValueSet);
        return $this->value;
    }

    /**
     * @param T $elseValue
     *
     * @return T
     *
     * @noinspection PhpUnused
     */
    public function getValueOr($elseValue)
    {
        return $this->isValueSet ? $this->value : $elseValue;
    }

    public function isValueSet(): bool
    {
        return $this->isValueSet;
    }

    /**
     * @param T $value
     *
     * @return self<T>
     *
     * @noinspection PhpUnused
     */
    public function valueIfNotSet($value): self
    {
        return $this->isValueSet ? $this : Optional::value($value);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs($this->isValueSet ? $this->value : /** @lang text */ '<Optional NOT SET>');
    }
}
