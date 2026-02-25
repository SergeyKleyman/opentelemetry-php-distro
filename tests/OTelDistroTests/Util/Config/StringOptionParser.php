<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionParser<string>
 */
final class StringOptionParser extends OptionParser
{
    /** @inheritDoc */
    #[Override]
    public function parse(string $rawValue): string
    {
        return $rawValue;
    }
}
