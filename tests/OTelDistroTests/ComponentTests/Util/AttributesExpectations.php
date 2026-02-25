<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\Attributes;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;
use Override;
use PHPUnit\Framework\Assert;

/**
 * @phpstan-import-type ArrayValue from AttributesArrayExpectations
 */
final class AttributesExpectations implements ExpectationsInterface, LoggableInterface
{
    use ExpectationsTrait;
    use LoggableTrait;

    public readonly AttributesArrayExpectations $arrayExpectations;

    /**
     * @param array<string, ArrayValue> $attributes
     * @param array<string>             $notAllowedAttributes
     */
    public function __construct(
        array $attributes,
        bool $allowOtherKeysInActual = true,
        private readonly array $notAllowedAttributes = []
    ) {
        $this->arrayExpectations = new AttributesArrayExpectations($attributes, $allowOtherKeysInActual);
    }

    public static function matchAny(): self
    {
        /** @var ?self $cached */
        static $cached = null;
        return $cached ??= new self([], allowOtherKeysInActual: true);
    }

    /**
     * @phpstan-param ArrayValue $value
     */
    public function with(string $key, array|bool|float|int|null|string|ExpectationsInterface $value): self
    {
        return new self($this->arrayExpectations->add($key, $value)->expectedArray, $this->arrayExpectations->allowOtherKeysInActual, $this->notAllowedAttributes);
    }

    public function withNotAllowed(string $key): self
    {
        $notAllowedAttributes = $this->notAllowedAttributes;
        $notAllowedAttributes[] = $key;
        return new self($this->arrayExpectations->expectedArray, $this->arrayExpectations->allowOtherKeysInActual, $notAllowedAttributes);
    }

    #[Override]
    public function assertMatchesMixed(mixed $actual): void
    {
        Assert::assertInstanceOf(Attributes::class, $actual);
        $this->assertMatches($actual);
    }

    public function assertMatches(Attributes $actual): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $this->arrayExpectations->assertMatches($actual);

        $dbgCtx->pushSubScope();
        foreach ($this->notAllowedAttributes as $notAllowedAttributeName) {
            $dbgCtx->resetTopSubScope(compact('notAllowedAttributeName'));
            Assert::assertFalse($actual->keyExists($notAllowedAttributeName));
        }
        $dbgCtx->popSubScope();
    }
}
