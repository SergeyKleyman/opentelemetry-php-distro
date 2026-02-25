<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
enum TestGroupName
{
    case smoke;
    case does_not_require_external_services;
    case requires_external_services;

    public function doesRequireExternalServices(): bool
    {
        return match ($this) {
            self::smoke, self::requires_external_services => true,
            self::does_not_require_external_services => false,
        };
    }
}
