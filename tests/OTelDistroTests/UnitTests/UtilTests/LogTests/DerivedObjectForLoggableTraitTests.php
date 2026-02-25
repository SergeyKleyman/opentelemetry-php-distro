<?php

/** @noinspection PhpUnusedPrivateFieldInspection, PhpPrivateFieldCanBeLocalVariableInspection */

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\LogTests;

class DerivedObjectForLoggableTraitTests extends ObjectForLoggableTraitTests
{
    private float $derivedFloatProp = 1.5; // @phpstan-ignore property.onlyWritten
    private string $anotherExcludedProp = 'anotherExcludedProp value'; // @phpstan-ignore property.onlyWritten

    /**
     * @return array<string>
     */
    protected static function propertiesExcludedFromLogImpl(): array
    {
        return array_merge(parent::propertiesExcludedFromLogImpl(), ['anotherExcludedProp']);
    }
}
