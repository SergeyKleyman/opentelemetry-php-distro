<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class ClassicFormatStackTraceFrame implements LoggableInterface
{
    /**
     * @param ?string      $file
     * @param ?int         $line
     * @param ?string      $class
     * @param ?bool        $isStaticMethod
     * @param ?string      $function
     * @param ?object      $thisObj
     * @param null|mixed[] $args
     *
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function __construct(
        public ?string $file = null,
        public ?int $line = null,
        public ?string $class = null,
        public ?bool $isStaticMethod = null,
        public ?string $function = null,
        public ?object $thisObj = null,
        public ?array $args = null
    ) {
    }

    public function canBeSameCall(ClassicFormatStackTraceFrame $other): bool
    {
        return (
            ($this->file === $other->file)
            && ($this->class === $other->class)
            && ($this->isStaticMethod === $other->isStaticMethod)
            && ($this->function === $other->function)
            && ($this->thisObj === $other->thisObj)
        );
    }

    #[Override]
    public function toLog(LogStreamInterface $stream): void
    {
        $nonNullProps = [];
        foreach ($this as $propName => $propVal) { // @phpstan-ignore foreach.nonIterable
            /** @var string $propName */
            if ($propVal === null || $propName === 'isStaticMethod') {
                continue;
            }
            $nonNullProps[$propName] = $propVal;
        }
        $stream->toLogAs($nonNullProps);
    }
}
