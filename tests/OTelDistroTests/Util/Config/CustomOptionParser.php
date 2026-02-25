<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Closure;
use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @template T
 *
 * @extends OptionParser<T>
 */
final class CustomOptionParser extends OptionParser
{
    /**
     * @param Closure(string): T $parseFunc
     */
    public function __construct(private readonly Closure $parseFunc)
    {
    }

    /** @inheritDoc */
    #[Override]
    public function parse(string $rawValue): mixed
    {
        return ($this->parseFunc)($rawValue);
    }
}
