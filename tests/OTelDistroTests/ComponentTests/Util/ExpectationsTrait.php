<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\DebugContext;
use Override;
use PHPUnit\Framework\Assert;

trait ExpectationsTrait
{
    #[Override]
    public function assertMatchesMixed(mixed $actual): void
    {
        $this->assertObjectMatchesTraitImpl($this, $actual);
    }

    protected static function assertValueMatches(mixed $expected, mixed $actual): void
    {
        if (is_object($expected)) {
            self::assertObjectMatches($expected, $actual);
            return;
        }

        Assert::assertSame($expected, $actual);
    }

    protected static function assertObjectMatches(object $expected, mixed $actual): void
    {
        if ($expected instanceof ExpectationsInterface) {
            $expected->assertMatchesMixed($actual);
            return;
        }

        static::assertObjectMatchesTraitImpl($expected, $actual);
    }

    protected static function assertObjectMatchesTraitImpl(object $expected, mixed $actual): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        Assert::assertIsObject($actual);

        /** @var string $propName */
        foreach ($expected as $propName => $expectationsPropValue) { // @phpstan-ignore foreach.nonIterable
            $dbgCtx->add(compact('propName', 'expectationsPropValue'));
            Assert::assertTrue(property_exists($actual, $propName));
            static::assertValueMatches($expectationsPropValue, $actual->$propName);
        }
    }
}
