<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @phpstan-import-type PreProcessMessageCallback from AssertionFailedError
 *
 * @phpstan-type CallStack non-empty-list<ClassicFormatStackTraceFrame>
 * @phpstan-type ScopeContext array<string, mixed>
 * @phpstan-type ScopeNameToContext array<string, ScopeContext>
 * @phpstan-type ConfigOptionName DebugContextConfig::*_OPTION_NAME
 * @phpstan-type ConfigStore array<ConfigOptionName, bool>
 */
final class DebugContext
{
    use StaticClassTrait;

    public const THIS_CONTEXT_KEY = 'this';

    public const TEXT_ADDED_TO_ASSERTION_MESSAGE_WHEN_DISABLED = 'DebugContext is DISABLED!';

    /**
     * Out parameter is used instead of return value to make harder to discard the scope object reference
     * thus making stay alive until the scope ends
     *
     * @param ?DebugContextScopeRef &$scopeVar
     * @param ScopeContext           $initialCtx
     *
     * @param-out DebugContextScopeRef $scopeVar
     */
    public static function getCurrentScope(/* out */ ?DebugContextScopeRef &$scopeVar, array $initialCtx = []): void
    {
        DebugContextSingleton::singletonInstance()->getCurrentScope(/* out */ $scopeVar, $initialCtx, numberOfStackFramesToSkip: 1);
    }

    /**
     * @return ScopeNameToContext
     */
    public static function getContextsStack(): array
    {
        return DebugContextSingleton::singletonInstance()->getContextsStack(numberOfStackFramesToSkip: 1);
    }

    public static function reset(): void
    {
        DebugContextSingleton::singletonInstance()->reset();
    }

    public static function ensureInited(): void
    {
        DebugContextSingleton::singletonInstance();
    }

    public static function extractAddedTextFromMessage(string $message): ?string
    {
        return DebugContextSingleton::singletonInstance()->extractAddedTextFromMessage($message);
    }

    /**
     * @return ?ScopeNameToContext
     */
    public static function extractContextsStackFromMessage(string $message): ?array
    {
        return DebugContextSingleton::singletonInstance()->extractContextsStackFromMessage($message);
    }
}
