<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\Util\DataProviderForTestBuilder;
use OTelDistroTests\Util\FlagsUtil;
use OTelDistroTests\Util\IterableUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FlagsUtilTest extends TestCase
{
    /**
     * @return iterable<array{int, array<int, string>, list<string>}>
     */
    public static function dataProviderForTestConvertFlagsToHumanReadable(): iterable
    {
        /**
         * @return iterable<array{int, array<int, string>, list<string>}>
         */
        $genDataSets = function (): iterable {
            $hasIsRemoteMask = 256;
            $isRemoteMask = 512;
            $maskToName = [
                $hasIsRemoteMask => 'HAS_IS_REMOTE',
                $isRemoteMask    => 'IS_REMOTE',
            ];

            yield [0, $maskToName, []];
            yield [1, $maskToName, []];
            yield [$hasIsRemoteMask - 1, $maskToName, []];
            yield [$hasIsRemoteMask, $maskToName, ['HAS_IS_REMOTE']];
            yield [($hasIsRemoteMask | 1), $maskToName, ['HAS_IS_REMOTE']];
            yield [$isRemoteMask, $maskToName, ['IS_REMOTE']];
            yield [($isRemoteMask | 2), $maskToName, ['IS_REMOTE']];
            yield [($hasIsRemoteMask | $isRemoteMask), $maskToName, ['HAS_IS_REMOTE', 'IS_REMOTE']];
            yield [($hasIsRemoteMask | $isRemoteMask | 4), $maskToName, ['HAS_IS_REMOTE', 'IS_REMOTE']];
        };

        return DataProviderForTestBuilder::keyEachDataSetWithDbgDesc($genDataSets);
    }

    /**
     * @param array<int, string> $maskToName
     * @param list<string>       $expectedResult
     */
    #[DataProvider('dataProviderForTestConvertFlagsToHumanReadable')]
    public function testExtractBitNames(int $flags, array $maskToName, array $expectedResult): void
    {
        $actualResult = IterableUtil::toList(FlagsUtil::extractBitNames($flags, $maskToName));
        self::assertSame($expectedResult, $actualResult);
    }
}
