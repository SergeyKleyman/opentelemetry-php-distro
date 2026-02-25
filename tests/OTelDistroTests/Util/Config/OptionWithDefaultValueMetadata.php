<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @template   TParsedValue
 *
 * @extends    OptionMetadata<TParsedValue>
 */
abstract class OptionWithDefaultValueMetadata extends OptionMetadata
{
    /**
     * @param OptionParser<TParsedValue> $parser
     * @param TParsedValue               $defaultValue
     */
    public function __construct(
        public readonly OptionParser $parser,
        public readonly mixed $defaultValue
    ) {
    }

    /**
     * @inheritDoc
     *
     * @return OptionParser<TParsedValue>
     */
    #[Override]
    public function parser(): OptionParser
    {
        return $this->parser;
    }

    /**
     * @inheritDoc
     *
     * @return TParsedValue
     */
    #[Override]
    public function defaultValue(): mixed
    {
        return $this->defaultValue;
    }
}
