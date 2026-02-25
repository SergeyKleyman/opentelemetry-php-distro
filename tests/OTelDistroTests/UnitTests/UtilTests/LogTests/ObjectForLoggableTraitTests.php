<?php

/** @noinspection PhpUnusedPrivateFieldInspection, PhpPrivateFieldCanBeLocalVariableInspection */

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests\LogTests;

use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LoggableTrait;

class ObjectForLoggableTraitTests implements LoggableInterface
{
    use LoggableTrait;

    private static bool $shouldExcludeProp = true;
    private static ?string $logWithClassNameValue = null;

    private int $intProp; // @phpstan-ignore property.onlyWritten
    private string $stringProp; // @phpstan-ignore property.onlyWritten
    private ?string $nullableStringProp = null; // @phpstan-ignore property.onlyWritten, property.unusedType
    private string $excludedProp = 'excludedProp value'; // @phpstan-ignore property.onlyWritten
    public string $lateInitProp;
    private ?ObjectForLoggableTraitTests $recursiveProp; // @phpstan-ignore property.onlyWritten

    public function __construct(int $intProp = 123, string $stringProp = 'Abc', ?ObjectForLoggableTraitTests $recursiveProp = null)
    {
        $this->intProp = $intProp;
        $this->stringProp = $stringProp;
        $this->recursiveProp = $recursiveProp;
    }

    public static function logWithoutClassName(): void
    {
        self::$logWithClassNameValue = null;
    }

    public static function logWithCustomClassName(string $className): void
    {
        self::$logWithClassNameValue = $className;
    }

    public static function logWithShortClassName(): void
    {
        self::$logWithClassNameValue = ClassNameUtil::fqToShort(static::class);
    }

    protected static function classNameToLog(): ?string
    {
        return self::$logWithClassNameValue;
    }

    public static function shouldExcludeProp(bool $shouldExcludeProp = true): void
    {
        self::$shouldExcludeProp = $shouldExcludeProp;
    }

    /**
     * @return array<string>
     */
    protected static function propertiesExcludedFromLogImpl(): array
    {
        return ['excludedProp'];
    }

    /**
     * @return array<string>
     */
    protected static function propertiesExcludedFromLog(): array
    {
        return self::$shouldExcludeProp ? static::propertiesExcludedFromLogImpl() : [];
    }
}
