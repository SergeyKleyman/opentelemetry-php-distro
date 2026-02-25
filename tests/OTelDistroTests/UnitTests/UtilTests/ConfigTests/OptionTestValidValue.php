<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;

/**
 * @template TParsedValue
 */
final class OptionTestValidValue implements LoggableInterface
{
    use LoggableTrait;

    public string $rawValue;

    /** @var TParsedValue */
    public mixed $parsedValue;

    /**
     * @param TParsedValue $parsedValue
     */
    public function __construct(string $rawValue, $parsedValue)
    {
        $this->rawValue = $rawValue;
        $this->parsedValue = $parsedValue;
    }
}
