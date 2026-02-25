<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\LogTests;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;

class ObjectWithResourceForTests implements LoggableInterface
{
    use LoggableTrait;

    /** @var resource */
    private $resourceField; // @phpstan-ignore property.unused
}
