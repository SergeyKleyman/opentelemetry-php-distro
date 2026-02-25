<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\ComponentTests\Util\UrlUtil;
use OTelDistroTests\Util\TestCaseBase;

class UrlUtilTest extends TestCaseBase
{
    /**
     * @return array<array<string|int|null>>
     *
     * @noinspection SpellCheckingInspection
     */
    public static function dataProviderForSplitHostPort(): array
    {
        return [
            ['my_host_1', 'my_host_1', null],
            ['my-host-2:2', 'my-host-2', 2],
            ['my-host-3:', 'my-host-3', null],
            [':3', null, 3],
            ['4-my-host:65535', '4-my-host', 65535],
            ['my-host-5:abc', 'my-host-5', null],
            [' my-host-6 : 123 ', 'my-host-6', 123],
            [' my-host-7  : abc ', 'my-host-7', null],
            ['6.77.89.102', '6.77.89.102', null],
            ['255.254.253.252', '255.254.253.252', null],
            ['255.254.253.252:7654', '255.254.253.252', 7654],
            ['255.254.253.252:', '255.254.253.252', null],
            ['::1', '::1', null],
            ['[::1]:88', '::1', 88],
            ['[ ::1 ] : 88', '::1', 88],
            ['[::1]:', '::1', null],
            [' [ ::1 ] : ', '::1', null],
            ['[::1]', '::1', null],
            [' [ ::1 ] ', '::1', null],
            [' [ ::1 ] ', '::1', null],
            ['fe80::dcdf:ebd9:b60a:b3fb', 'fe80::dcdf:ebd9:b60a:b3fb', null],
            ['[fe80::dcdf:ebd9:b60a:b3fb]:9999', 'fe80::dcdf:ebd9:b60a:b3fb', 9999],
            ['[fe80::dcdf:ebd9:b60a:b3fb]', 'fe80::dcdf:ebd9:b60a:b3fb', null],
            ['[fe80::dcdf:ebd9:b60a:b3fb]:', 'fe80::dcdf:ebd9:b60a:b3fb', null],
            ['fe80::dcdf:ebd9:b60a:b3fb%3', 'fe80::dcdf:ebd9:b60a:b3fb%3', null],
            ['[fe80::dcdf:ebd9:b60a:b3fb%3]:9999', 'fe80::dcdf:ebd9:b60a:b3fb%3', 9999],
            ['[fe80::dcdf:ebd9:b60a:b3fb%3]:', 'fe80::dcdf:ebd9:b60a:b3fb%3', null],
            ['[fe80::dcdf:ebd9:b60a:b3fb%3]', 'fe80::dcdf:ebd9:b60a:b3fb%3', null],
        ];
    }

    /**
     * @dataProvider dataProviderForSplitHostPort
     *
     * @param string      $inputHostPort
     * @param string|null $expectedHost
     * @param int|null    $expectedPort
     */
    public function testSplitHostPort(string $inputHostPort, ?string $expectedHost, ?int $expectedPort): void
    {
        $actualHost = null;
        $actualPort = null;
        self::assertTrue(UrlUtil::splitHostPort($inputHostPort, /* ref */ $actualHost, /* ref */ $actualPort));
        self::assertSame($expectedHost, $actualHost);
        self::assertSame($expectedPort, $actualPort);
    }
}
