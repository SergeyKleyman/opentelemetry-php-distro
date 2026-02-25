<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;
use OpenTelemetry\Distro\Util\WildcardListMatcher;

/**
 * @implements OptionTestValuesGeneratorInterface<WildcardListMatcher>
 */
final class WildcardListOptionTestValuesGenerator implements OptionTestValuesGeneratorInterface
{
    use SingletonInstanceTrait;

    /**
     * @return iterable<OptionTestValidValue<WildcardListMatcher>>
     */
    public function validValues(): iterable
    {
        return [];
    }

    public function invalidRawValues(): iterable
    {
        return [];
    }
}
