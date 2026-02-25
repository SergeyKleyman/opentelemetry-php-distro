<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use RuntimeException;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
class ConfigException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
