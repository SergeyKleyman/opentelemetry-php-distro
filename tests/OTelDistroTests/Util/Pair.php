<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;

/**
 * @template TFirst
 * @template TSecond
 */
final class Pair implements LoggableInterface
{
    use LoggableTrait;

    /** @var TFirst */
    public $first;

    /** @var TSecond */
    public $second;

    /**
     * @param TFirst $first
     * @param TSecond $second
     */
    public function __construct($first, $second)
    {
        $this->first = $first;
        $this->second = $second;
    }
}
