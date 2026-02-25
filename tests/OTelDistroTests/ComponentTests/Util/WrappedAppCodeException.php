<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Throwable;

final class WrappedAppCodeException extends ComponentTestsInfraException
{
    private Throwable $wrappedException;

    public function __construct(Throwable $wrappedException)
    {
        parent::__construct();
        $this->wrappedException = $wrappedException;
    }

    public function wrappedException(): Throwable
    {
        return$this->wrappedException;
    }
}
