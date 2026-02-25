<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

interface ExpectationsInterface
{
    public function assertMatchesMixed(mixed $actual): void;
}
