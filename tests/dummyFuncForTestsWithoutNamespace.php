<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

const OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_FUNCTION = 'dummyFuncForTestsWithoutNamespace';
const OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_FILE = __FILE__;
const OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CONTINUATION_CALL_LINE = 22;

/**
 * @template TReturnValue
 *
 * @param callable(): TReturnValue $continuation
 *
 * @return TReturnValue
 */
function dummyFuncForTestsWithoutNamespace(callable $continuation)
{
    Assert::assertSame(OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_FUNCTION, __FUNCTION__);
    Assert::assertSame(OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CONTINUATION_CALL_LINE, __LINE__ + 1);
    return $continuation(); // OTEL_PHP_TESTS_DUMMY_FUNC_FOR_TESTS_WITHOUT_NAMESPACE_CONTINUATION_CALL_LINE should be this line number
}
