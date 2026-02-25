<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\StackTraceUtil;

final class StackTraceFrameExpectationsBuilder
{
    protected NullableStringExpectations $file;

    /** @var LeafExpectations<?positive-int> */
    protected LeafExpectations $line;

    protected NullableStringExpectations $function;

    public function __construct()
    {
        $this->file = NullableStringExpectations::matchAny();
        $this->line = LeafExpectations::matchAny(); // @phpstan-ignore assign.propertyType
        $this->function = NullableStringExpectations::matchAny();
    }

    /**
     * @return $this
     */
    public function file(string $file): self
    {
        $this->file = NullableStringExpectations::literal($file);
        return $this;
    }

    /**
     * @param positive-int $line
     *
     * @return $this
     */
    public function line(int $line): self
    {
        $this->line = LeafExpectations::expectedValue($line); // @phpstan-ignore assign.propertyType
        return $this;
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function noFileLine(): self
    {
        $this->file = NullableStringExpectations::literal(null);
        $this->line = LeafExpectations::expectedValue(null); // @phpstan-ignore assign.propertyType
        return $this;
    }

    /**
     * @return $this
     */
    public function function(string $funcName): self
    {
        $this->function = NullableStringExpectations::literal($funcName);
        return $this;
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function staticClassMethod(string $className, string $methodName): self
    {
        return $this->function($className . StackTraceUtil::METHOD_IS_STATIC_KIND_VALUE . $methodName);
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function instanceClassMethod(string $className, string $methodName): self
    {
        return $this->function($className . StackTraceUtil::METHOD_IS_INSTANCE_KIND_VALUE . $methodName);
    }

    /**
     * @return $this
     *
     * @noinspection PhpUnused
     */
    public function functionRegEx(string $functionRegEx): self
    {
        $this->function = NullableStringExpectations::regex($functionRegEx);
        return $this;
    }

    public function build(): StackTraceFrameExpectations
    {
        return new StackTraceFrameExpectations($this->file, $this->line, $this->function);
    }
}
