<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

/**
 * @template TParsedValue
 */
interface OptionTestValuesGeneratorInterface
{
    public const NUMBER_OF_RANDOM_VALUES_TO_TEST = 10;

    /**
     * @return iterable<OptionTestValidValue<TParsedValue>>
     */
    public function validValues(): iterable;

    /**
     * @return iterable<string>
     */
    public function invalidRawValues(): iterable;
}
