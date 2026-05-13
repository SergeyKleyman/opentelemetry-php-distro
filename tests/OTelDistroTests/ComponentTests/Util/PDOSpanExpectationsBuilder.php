<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use PDO;
use PDOStatement;

class PDOSpanExpectationsBuilder extends DbSpanExpectationsBuilder
{
    public function buildForPDOClassMethod(string $methodName, ?string $dbQueryText = null): SpanExpectations
    {
        return (clone $this)
            ->nameAndCodeAttributesForClassMethod(PDO::class, $methodName)
            ->optionalDbQueryText($dbQueryText)
            ->build();
    }

    public function buildForPDOStatementClassMethod(string $methodName): SpanExpectations
    {
        return (clone $this)->nameAndCodeAttributesForClassMethod(PDOStatement::class, $methodName)->build();
    }
}
