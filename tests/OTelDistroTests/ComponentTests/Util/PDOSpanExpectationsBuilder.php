<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\ClassNameUtil;
use PDO;
use PDOStatement;

class PDOSpanExpectationsBuilder extends DbSpanExpectationsBuilder
{
    public function buildForPDOClassMethod(string $methodName, ?bool $isStaticMethod = null, ?string $dbQueryText = null): SpanExpectations
    {
        return (clone $this)
            ->nameAndCodeFunctionUsingClassMethod(ClassNameUtil::fqToShort(PDO::class), $methodName, $isStaticMethod)
            ->optionalDbQueryText($dbQueryText)
            ->build();
    }

    public function buildForPDOStatementClassMethod(string $methodName, ?bool $isStaticMethod = null): SpanExpectations
    {
        return (clone $this)->nameAndCodeFunctionUsingClassMethod(ClassNameUtil::fqToShort(PDOStatement::class), $methodName, $isStaticMethod)->build();
    }
}
