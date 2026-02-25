<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

enum EnumOptionsParsingTestDummyBackedEnum: string
{
    case enumEntry = 'enumEntry_value';
    case enumEntryWithSuffix = 'enumEntryWithSuffix_value';
    case enumEntryWithSuffix2 = 'enumEntryWithSuffix2_value';
    case anotherEnumEntry = 'anotherEnumEntry_value';
}
