<?php

declare(strict_types=1);

namespace OpenTelemetry\DistroTests;

use PHPUnit\Framework\Assert;

const DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_NAMESPACE = __NAMESPACE__;
const DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_FUNCTION = DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_NAMESPACE . '\\' . 'dummyFuncForTestsWithNamespace';
const DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_FILE = __FILE__;
const DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_CONTINUATION_CALL_LINE = 25;

/**
 * @template TReturnValue
 *
 * @param callable(): TReturnValue $continuation
 *
 * @return TReturnValue
 */
function dummyFuncForTestsWithNamespace(callable $continuation)
{
    Assert::assertSame(DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_FUNCTION, __FUNCTION__); // @phpstan-ignore staticMethod.alreadyNarrowedType
    Assert::assertSame(DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_CONTINUATION_CALL_LINE, __LINE__ + 1); // @phpstan-ignore staticMethod.alreadyNarrowedType
    return $continuation(); // DUMMY_FUNC_FOR_TESTS_WITH_NAMESPACE_CONTINUATION_CALL_LINE should be this line number
}
