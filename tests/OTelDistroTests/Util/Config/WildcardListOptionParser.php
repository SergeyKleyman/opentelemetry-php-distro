<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Util\WildcardListMatcher;
use Override;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionParser<WildcardListMatcher>
 */
final class WildcardListOptionParser extends OptionParser
{
    /** @inheritDoc */
    #[Override]
    public function parse(string $rawValue): WildcardListMatcher
    {
        return self::staticParse($rawValue);
    }

    private static function staticParse(string $rawValue): WildcardListMatcher
    {
        /**
         * @return iterable<string>
         */
        $splitWildcardExpr = function () use ($rawValue): iterable {
            foreach (explode(',', $rawValue) as $listElementRaw) {
                yield trim($listElementRaw);
            }
        };

        return new WildcardListMatcher($splitWildcardExpr());
    }
}
