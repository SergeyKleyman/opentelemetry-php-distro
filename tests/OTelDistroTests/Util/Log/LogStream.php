<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
class LogStream implements LogStreamInterface
{
    public mixed $value;

    public function isLastLevel(): bool
    {
        return false;
    }

    public function toLogAs(mixed $value): void
    {
        $this->value = $value;
    }
}
