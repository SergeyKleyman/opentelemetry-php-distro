<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use AllowDynamicProperties;

#[AllowDynamicProperties]
final class DummyObjectForTests
{
    /** @var string */
    public string $dummyPublicStringProperty;

    public function __construct(string $dummyPublicStringProperty)
    {
        $this->dummyPublicStringProperty = $dummyPublicStringProperty;
    }
}
