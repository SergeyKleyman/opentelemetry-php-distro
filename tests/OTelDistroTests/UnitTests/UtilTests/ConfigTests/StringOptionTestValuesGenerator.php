<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\ConfigTests;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\RangeUtil;
use OpenTelemetry\Distro\Util\TextUtil;
use OTelDistroTests\Util\RandomUtil;
use OTelDistroTests\Util\TextUtilForTests;

/**
 * @implements OptionTestValuesGeneratorInterface<string>
 */
final class StringOptionTestValuesGenerator implements OptionTestValuesGeneratorInterface
{
    use SingletonInstanceTrait;

    /**
     * @return iterable<int>
     */
    private static function charsToUse(): iterable
    {
        // latin letters
        foreach (RangeUtil::generateFromToIncluding(ord('A'), ord('Z')) as $charAsInt) {
            yield $charAsInt;
            yield TextUtil::flipLetterCase($charAsInt);
        }

        // digits
        foreach (RangeUtil::generateFromToIncluding(ord('0'), ord('9')) as $charAsInt) {
            yield $charAsInt;
        }

        // punctuation
        yield from TextUtilForTests::iterateOverChars(',:;.!?');

        yield from TextUtilForTests::iterateOverChars('@#$%&*()<>{}[]+-=_~^');
        yield ord('/');
        yield ord('|');
        yield ord('\\');
        yield ord('`');
        yield ord('\'');
        yield ord('"');

        // whitespace
        yield from TextUtilForTests::iterateOverChars(" \t\r\n");
    }

    /**
     * @return iterable<string>
     */
    private function validStrings(): iterable
    {
        yield '';
        yield 'A';
        yield 'abc';
        yield 'abC 123 Xyz';

        /** @var array<int> $charsToUse */
        $charsToUse = IterableUtil::toList(self::charsToUse());

        $stringFromAllCharsToUse = '';
        foreach ($charsToUse as $charToUse) {
            $stringFromAllCharsToUse .= chr($charToUse);
        }
        yield $stringFromAllCharsToUse;

        // any two chars (even the same one twice)
        foreach (RangeUtil::generateUpTo(count($charsToUse)) as $i) {
            foreach (RangeUtil::generateUpTo(count($charsToUse)) as $j) {
                yield chr($charsToUse[$i]) . chr($charsToUse[$j]);
            }
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach (RangeUtil::generateUpTo(self::NUMBER_OF_RANDOM_VALUES_TO_TEST) as $_) {
            $numberOfChars = RandomUtil::generateIntInRange(1, count($charsToUse));
            $randString = '';
            /** @noinspection PhpUnusedLocalVariableInspection */
            foreach (RangeUtil::generateUpTo($numberOfChars) as $__) {
                $randString .= chr(RandomUtil::generateIntInRange(0, count($charsToUse) - 1));
            }
            yield $randString;
        }
    }

    /**
     * @return iterable<OptionTestValidValue<string>>
     */
    public function validValues(): iterable
    {
        foreach ($this->validStrings() as $validString) {
            yield new OptionTestValidValue($validString, trim($validString));
        }
    }

    public function invalidRawValues(): iterable
    {
        return [];
    }
}
