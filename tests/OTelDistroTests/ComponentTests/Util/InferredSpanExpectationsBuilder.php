<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\SpanKind;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;

class InferredSpanExpectationsBuilder extends SpanExpectationsBuilder
{
    public const IS_INFERRED_ATTRIBUTE_NAME = 'is_inferred';

    public function __construct()
    {
        parent::__construct();

        $this->kind(SpanKind::internal)
             ->addAttribute(self::IS_INFERRED_ATTRIBUTE_NAME, true);
    }

    private static function buildFor(self $builderClone, StackTraceExpectations $stackTrace, ?int $codeLineNumber): SpanExpectations
    {
        if ($codeLineNumber !== null) {
            $builderClone->addAttribute(CodeAttributes::CODE_LINE_NUMBER, $codeLineNumber);
        }
        return $builderClone->stackTrace($stackTrace)->build();
    }

    public function buildForStaticMethod(string $className, string $methodName, StackTraceExpectations $stackTrace, ?int $codeLineNumber = null): SpanExpectations
    {
        return self::buildFor((clone $this)->nameAndCodeAttributesForClassMethod($className, $methodName), $stackTrace, $codeLineNumber);
    }

    public function buildForFunction(string $funcName, StackTraceExpectations $stackTrace, ?int $codeLineNumber = null): SpanExpectations
    {
        return self::buildFor((clone $this)->nameAndCodeAttributesForFunction($funcName), $stackTrace, $codeLineNumber);
    }
}
