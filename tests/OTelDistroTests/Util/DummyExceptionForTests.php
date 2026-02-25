<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use RuntimeException;
use Throwable;

/** @noinspection PhpUnused */
class DummyExceptionForTests extends RuntimeException
{
    /** @noinspection PhpUnused */
    public const NAMESPACE = __NAMESPACE__;
    /** @noinspection PhpUnused */
    public const FQ_CLASS_NAME = __CLASS__;

    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
