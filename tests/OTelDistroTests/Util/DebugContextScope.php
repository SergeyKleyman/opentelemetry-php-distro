<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-import-type ScopeContext from DebugContext
 * @phpstan-import-type CallStack from DebugContext
 */
final class DebugContextScope implements LoggableInterface
{
    /** @var non-empty-list<ScopeContext> */
    private array $subScopesContexts;

    /**
     * @param non-negative-int $callStackFrameIndex
     * @param ScopeContext     $initialCtx
     */
    public function __construct(
        private readonly DebugContextSingleton $containingStack,
        public readonly int $callStackFrameIndex,
        array $initialCtx
    ) {
        $this->subScopesContexts = [$initialCtx];
    }

    /**
     * @param ScopeContext  $from
     * @param ScopeContext &$to
     *
     * @param-out ScopeContext $to
     */
    public static function appendContext(array $from, /* in,out */ array &$to): void
    {
        // Remove keys that exist in the new context to make the new entry the last in added order
        ArrayUtilForTests::removeByKeys(/* in,out */ $to, IterableUtil::keys($from));
        ArrayUtilForTests::append(from: $from, to: $to);
    }

    /**
     * @param ScopeContext $ctx
     */
    public function add(array $ctx): void
    {
        self::appendContext(from: $ctx, to: $this->subScopesContexts[array_key_last($this->subScopesContexts)]);
    }

    /**
     * @phpstan-param ScopeContext $ctx
     */
    public function pushSubScope(array $ctx = []): void
    {
        $this->subScopesContexts[] = $ctx;
    }

    public function popSubScope(): void
    {
        Assert::assertGreaterThanOrEqual(2, $this->subScopesContexts);
        AssertEx::notNull(array_pop($this->subScopesContexts)); // @phpstan-ignore assign.propertyType
    }

    /**
     * @phpstan-param ScopeContext $ctx
     */
    public function resetTopSubScope(array $ctx): void
    {
        $this->popSubScope();
        $this->pushSubScope($ctx);
    }

    /**
     * @param CallStack $newCallStack
     * @param non-negative-int $newCallStackFromFrameIndex
     */
    public function syncWithCallStack(array $newCallStack, int $newCallStackFromFrameIndex): bool
    {
        if (!RangeUtil::isInClosedRange($newCallStackFromFrameIndex, $this->callStackFrameIndex, count($newCallStack) - 1)) {
            return false;
        }

        foreach (RangeUtil::generateFromToIncluding($newCallStackFromFrameIndex, $this->callStackFrameIndex) as $frameIndex) {
            /** @var non-negative-int $frameIndex */
            $oldFrame = $this->containingStack->getSyncedWithCallStack()[$frameIndex];
            $newFrame = $newCallStack[$frameIndex];
            if (!$oldFrame->canBeSameCall($newFrame)) {
                return false;
            }
            // If source code line is different that means that all the scopes up to the top of the stack
            // are for calls different from the ones on the current calls stack
            if (($frameIndex !== $this->callStackFrameIndex) && ($oldFrame->line !== $newFrame->line)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return ScopeContext
     */
    public function getContext(): array
    {
        $result = [];
        foreach ($this->subScopesContexts as $subScopeCtx) {
            self::appendContext(from: $subScopeCtx, to: $result);
        }
        return $result;
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs(
            [
                'callStackFrameIndex' => $this->callStackFrameIndex,
                'subScopesContexts count' => count($this->subScopesContexts),
            ],
        );
    }
}
