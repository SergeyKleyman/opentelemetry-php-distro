<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

final class ParseException extends ConfigException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
