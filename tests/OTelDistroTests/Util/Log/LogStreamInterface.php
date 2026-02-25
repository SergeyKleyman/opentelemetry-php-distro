<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
interface LogStreamInterface
{
    public function toLogAs(mixed $value): void;

    public function isLastLevel(): bool;
}
