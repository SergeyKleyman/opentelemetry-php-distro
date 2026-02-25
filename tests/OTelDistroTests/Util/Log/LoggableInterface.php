<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface LoggableInterface
{
    /**
     * Used for logging subsystem to generate representation for this object
     *
     * @param LogStreamInterface $stream
     */
    public function toLog(LogStreamInterface $stream): void;
}
