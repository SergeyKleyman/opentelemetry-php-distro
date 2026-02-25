<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\Util\ClassNameUtil;
use PHPUnit\Framework\TestCase;

class ClassNameUtilTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function dataProviderForSplitFqClassName(): array
    {
        return [
            ['My\\Name\\Space\\MyClass', 'My\\Name\\Space', 'MyClass'],
            ['\\My\\Name\\Space\\MyClass', 'My\\Name\\Space', 'MyClass'],
            ['\\MyNameSpace\\MyClass', 'MyNameSpace', 'MyClass'],
            ['MyNameSpace\\MyClass', 'MyNameSpace', 'MyClass'],
            ['\\MyClass', '', 'MyClass'],
            ['MyClass', '', 'MyClass'],
            ['MyNameSpace\\', 'MyNameSpace', ''],
            ['\\MyNameSpace\\', 'MyNameSpace', ''],
            ['', '', ''],
            ['\\', '', ''],
            ['a\\', 'a', ''],
            ['\\a\\', 'a', ''],
            ['\\b', '', 'b'],
            ['\\\\', '', ''],
            ['\\\\\\', '\\', ''],
        ];
    }

    /**
     * @dataProvider dataProviderForSplitFqClassName
     *
     * @param string $fqClassName
     * @param string $expectedNamespace
     * @param string $expectedShortName
     */
    public function testSplitFqClassName(
        string $fqClassName,
        string $expectedNamespace,
        string $expectedShortName
    ): void {
        /** @var class-string $fqClassName */
        $actualNamespace = '';
        $actualShortName = '';
        ClassNameUtil::splitFqClassName($fqClassName, /* ref */ $actualNamespace, /* ref */ $actualShortName);
        self::assertSame($expectedNamespace, $actualNamespace);
        self::assertSame($expectedShortName, $actualShortName);
    }
}
