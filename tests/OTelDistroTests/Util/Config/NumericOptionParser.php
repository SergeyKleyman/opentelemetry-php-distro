<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @template TParsedValue of int|float
 *
 * @extends  OptionParser<TParsedValue>
 */
abstract class NumericOptionParser extends OptionParser
{
    /**
     * @phpstan-param ?TParsedValue $minValidValue
     * @phpstan-param ?TParsedValue $maxValidValue
     */
    public function __construct(
        private readonly null|int|float $minValidValue,
        private readonly null|int|float $maxValidValue,
    ) {
    }

    abstract protected function dbgValueTypeDesc(): string;

    abstract public static function isValidFormat(string $rawValue): bool;

    /**
     * @return TParsedValue
     */
    abstract protected function stringToNumber(string $rawValue): int|float;

    /** @inheritDoc */
    #[Override]
    public function parse(string $rawValue): int|float
    {
        if (!static::isValidFormat($rawValue)) {
            throw new ParseException(
                'Not a valid ' . $this->dbgValueTypeDesc() . " value. Raw option value: `''$rawValue'"
            );
        }

        $parsedValue = $this->stringToNumber($rawValue);

        if (
            (($this->minValidValue !== null) && ($parsedValue < $this->minValidValue))
            || (($this->maxValidValue !== null) && ($parsedValue > $this->maxValidValue))
        ) {
            throw new ParseException(
                'Value is not in range between the valid minimum and maximum values.'
                . ' Raw option value: `' . $rawValue . "'."
                . ' Parsed option value: ' . $parsedValue . '.'
                . ' The valid minimum value: ' . $this->minValidValue . '.'
                . ' The valid maximum value: ' . $this->maxValidValue . '.'
            );
        }

        return $parsedValue;
    }

    /**
     * @phpstan-return ?TParsedValue
     */
    public function minValidValue(): null|int|float
    {
        return $this->minValidValue;
    }

    /**
     * @phpstan-return ?TParsedValue
     */
    public function maxValidValue(): null|int|float
    {
        return $this->maxValidValue;
    }
}
