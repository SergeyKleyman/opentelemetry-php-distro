<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util\MySqli;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use mysqli_result;

/**
 * Code in this file is part of implementation internals and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class MySqliResultWrapped implements LoggableInterface
{
    use LoggableTrait;

    public function __construct(
        private readonly mysqli_result $wrappedObj,
        private readonly bool $isOOPApi
    ) {
    }

    public function numRows(): int|string
    {
        return $this->isOOPApi
            ? $this->wrappedObj->num_rows
            : mysqli_num_rows($this->wrappedObj);
    }

    /**
     * According to the docs https://www.php.net/manual/en/mysqli-result.fetch-assoc.php
     * return type is array|null|false
     * Returns
     *      - an associative array representing the fetched row,
     *          where each key in the array represents the name of one of the result set's columns,
     *      - null if there are no more rows in the result set
     *      - false on failure
     *
     * @return array<mixed>|null|false
     */
    public function fetchAssoc(): array|null|false
    {
        return $this->isOOPApi
            ? $this->wrappedObj->fetch_assoc()
            : mysqli_fetch_assoc($this->wrappedObj);
    }

    public function close(): void
    {
        $this->isOOPApi
            ? $this->wrappedObj->close()
            : mysqli_free_result($this->wrappedObj);
    }
}
