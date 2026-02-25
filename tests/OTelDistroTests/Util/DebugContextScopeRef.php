<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

/**
 * @phpstan-import-type ScopeContext from DebugContextScope
 */
final class DebugContextScopeRef
{
    public function __construct(
        private readonly ?DebugContextScope $scope,
    ) {
    }

    /**
     * @phpstan-param ScopeContext $ctx
     */
    public function add(array $ctx): void
    {
        $this->scope?->add($ctx);
    }

    public function pushSubScope(): void
    {
        $this->scope?->pushSubScope();
    }

    /**
     * @phpstan-param ScopeContext $ctx
     */
    public function resetTopSubScope(array $ctx): void
    {
        $this->scope?->resetTopSubScope($ctx);
    }

    public function popSubScope(): void
    {
        $this->scope?->popSubScope();
    }
}
