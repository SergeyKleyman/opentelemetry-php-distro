<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\ComponentTests\Util\OtlpData\SpanKind;
use OTelDistroTests\Util\AssertEx;
use OpenTelemetry\SemConv\TraceAttributes;

/**
 * @phpstan-import-type ArrayValue from AttributesExpectations as SpanAttributesExpectationsArrayValue
 */
class SpanExpectationsBuilder
{
    private const CLASS_AND_METHOD_SEPARATOR = '::';

    protected StringExpectations $name;

    /** @var LeafExpectations<SpanKind> */
    protected LeafExpectations $kind;

    protected AttributesExpectations $attributes;

    protected StackTraceExpectations $stackTrace;

    public function __construct()
    {
        $this->name = StringExpectations::matchAny();
        $this->kind = LeafExpectations::matchAny(); // @phpstan-ignore assign.propertyType
        $this->attributes = AttributesExpectations::matchAny();
        $this->stackTrace = StackTraceExpectations::matchAny();
    }

    /**
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = StringExpectations::literal($name);
        return $this;
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function nameRegEx(string $nameRegEx): self
    {
        $this->name = StringExpectations::regex($nameRegEx);
        return $this;
    }

    /**
     * @return $this
     */
    public function kind(SpanKind $kind): self
    {
        $this->kind = LeafExpectations::expectedValue($kind); // @phpstan-ignore assign.propertyType
        return $this;
    }

    /**
     * @return $this
     */
    public function attributes(AttributesExpectations $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return $this
     */
    public function nameAndCodeAttributesUsingFuncName(string $funcName): self
    {
        $this->name($funcName);
        return $this->addAttribute(TraceAttributes::CODE_FUNCTION_NAME, $funcName);
    }

    /**
     * @return $this
     */
    public function nameUsingClassMethod(string $className, string $methodName, ?bool $isStaticMethod = null): self
    {
        return $this->name(AssertEx::notNull(self::buildNameFromClassMethod($className, $methodName, $isStaticMethod)));
    }

    /**
     * @return $this
     */
    public function nameAndCodeAttributesUsingClassMethod(string $className, string $methodName, ?bool $isStaticMethod = null): self
    {
        $this->nameUsingClassMethod($className, $methodName, $isStaticMethod);
        $this->addAttribute(TraceAttributes::CODE_NAMESPACE, $className);
        return $this->addAttribute(TraceAttributes::CODE_FUNCTION_NAME, $methodName);
    }

    /**
     * @return $this
     */
    public function nameAndCodeFunctionUsingClassMethod(string $className, string $methodName, ?bool $isStaticMethod = null): self
    {
        $this->nameUsingClassMethod($className, $methodName, $isStaticMethod);
        return $this->addAttribute(TraceAttributes::CODE_FUNCTION_NAME, $className . self::CLASS_AND_METHOD_SEPARATOR . $methodName);
    }

    private static function buildNameFromClassMethod(?string $classicName, ?string $methodName, /** @noinspection PhpUnusedParameterInspection */ ?bool $isStaticMethod = null): ?string
    {
        if ($methodName === null) {
            return null;
        }

        if ($classicName === null) {
            return $methodName;
        }

        return $classicName . self::CLASS_AND_METHOD_SEPARATOR . $methodName;
    }

    /**
     * @phpstan-param SpanAttributesExpectationsArrayValue $value
     *
     * @return $this
     */
    public function addAttribute(string $key, array|bool|float|int|null|string|ExpectationsInterface $value): self
    {
        $this->attributes = $this->attributes->with($key, $value);
        return $this;
    }

    /**
     * @return $this
     */
    public function addNotAllowedAttribute(string $key): self
    {
        $this->attributes = $this->attributes->withNotAllowed($key);
        return $this;
    }

    /**
     * @return $this
     */
    public function serverAddress(string $value): self
    {
        return $this->addAttribute(TraceAttributes::SERVER_ADDRESS, $value);
    }

    /**
     * @return $this
     */
    public function serverPort(int $value): self
    {
        return $this->addAttribute(TraceAttributes::SERVER_PORT, $value);
    }

    /**
     * @return $this
     */
    public function stackTrace(StackTraceExpectations $stackTrace): self
    {
        return $this->addAttribute(TraceAttributes::CODE_STACKTRACE, $stackTrace);
    }

    public function build(): SpanExpectations
    {
        return new SpanExpectations($this->name, $this->kind, $this->attributes);
    }
}
