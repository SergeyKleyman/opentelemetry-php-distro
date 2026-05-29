<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use Closure;
use Generator;
use Iterator;
use IteratorAggregate;
use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\Log\LoggableToString;
use ParseError;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Traversable;

/**
 * @phpstan-type EnvVars array<string, string>
 */
final class ReflectionUtil
{
    use StaticClassTrait;

    public const ARRAY_TYPE_NAME = 'array';
    public const CALLABLE_TYPE_NAME = 'callable';
    public const FLOAT_TYPE_NAME = 'float';
    public const INT_TYPE_NAME = 'int';
    public const ITERABLE_TYPE_NAME = 'iterable';
    public const MIXED_TYPE_NAME = 'mixed';
    public const NULL_TYPE_NAME = 'null';
    public const OBJECT_TYPE_NAME = 'object';

    private const UNION_TYPE_MEMBERS_SEPARATOR = '|';

    public static function canonicalizeReflectionTypeName(string $name): string
    {
        if (!str_contains($name, self::UNION_TYPE_MEMBERS_SEPARATOR)) {
            return $name;
        }

        /** @var list<string> $memberNames */
        $memberNames = AssertEx::arrayIsList(AssertEx::isArray(explode(self::UNION_TYPE_MEMBERS_SEPARATOR, $name)));
        return self::unionTypeMembersToCanonicalName($memberNames);
    }

    /**
     * @param list<string> $memberNames
     */
    public static function unionTypeMembersToCanonicalName(array $memberNames): string
    {
        sort(/* ref */ $memberNames, SORT_STRING);
        return implode(self::UNION_TYPE_MEMBERS_SEPARATOR, $memberNames);
    }

    public static function getReflectionTypeCanonicalName(ReflectionType $type): string
    {
        return self::canonicalizeReflectionTypeName($type->__toString());
    }

    public static function areEquivalentReflectionTypeNames(string $name1, string $name2): bool
    {
        return self::canonicalizeReflectionTypeName($name1) === self::canonicalizeReflectionTypeName($name2);
    }

    public static function areEqualReflectionTypes(ReflectionType $type1, ReflectionType $type2): bool
    {
        return self::areEquivalentReflectionTypeNames($type1->__toString(), $type2->__toString());
    }

    private static function canReflectionNamedTypeBeAssignedToReflectionNamedType(ReflectionNamedType $source, ReflectionNamedType $target): bool
    {
        if (($sourceName = $source->getName()) === ($targetName = $target->getName())) {
            return true;
        }
        if (
            (($sourceName === self::INT_TYPE_NAME) && ($targetName === self::FLOAT_TYPE_NAME))
            || (($sourceName === self::ARRAY_TYPE_NAME) && ($targetName === self::ITERABLE_TYPE_NAME))
            || (($sourceName === self::NULL_TYPE_NAME) && $target->allowsNull())
        ) {
            return true;
        }

        if (!(class_exists($sourceName) || interface_exists($sourceName))) {
            return false;
        }
        if ($targetName === self::OBJECT_TYPE_NAME) {
            return true;
        }
        if (($sourceName === Closure::class) && ($targetName === self::CALLABLE_TYPE_NAME)) {
            return true;
        }
        if ($targetName === self::ITERABLE_TYPE_NAME) {
            return ($sourceName === Traversable::class) || is_subclass_of($sourceName, Traversable::class);
        }

        // Check class inheritance/interfaces
        return (class_exists($targetName) || interface_exists($targetName)) && is_subclass_of($sourceName, $targetName);
    }

    public static function canReflectionTypeBeAssignedToReflectionType(ReflectionType $source, ReflectionType $target): bool
    {
        if (($target instanceof ReflectionNamedType) && ($target->getName() === self::MIXED_TYPE_NAME)) {
            return true;
        }

        // Handle nullability
        if ($source->allowsNull() && !$target->allowsNull()) {
            return false;
        }

        // Normalize types to arrays for easier comparison (PHP 8.0+)
        $sourceTypes = $source instanceof ReflectionUnionType ? $source->getTypes() : [$source];
        $targetTypes = $target instanceof ReflectionUnionType ? $target->getTypes() : [$target];

        // All provided types must be compatible with at least one target type
        foreach ($sourceTypes as $sourceType) {
            $foundMatch = false;
            foreach ($targetTypes as $targetType) {
                if (
                    self::canReflectionNamedTypeBeAssignedToReflectionNamedType(
                        AssertEx::isInstanceOf(ReflectionNamedType::class, $sourceType),
                        AssertEx::isInstanceOf(ReflectionNamedType::class, $targetType),
                    )
                ) {
                    $foundMatch = true;
                    break;
                }
            }
            if (!$foundMatch) {
                return false;
            }
        }

        return true;
    }

    public static function canValueBeAssignedToReflectionType(mixed $source, ReflectionType $target): bool
    {
        if ($source === null) {
            return $target->allowsNull();
        }
        return self::canReflectionTypeBeAssignedToReflectionType(self::getReflectionTypeForValue($source) ?? self::mixedReflectionType(), $target);
    }

    /**
     * @param ReflectionClass<object> $valueClass
     */
    private static function getReflectionTypeForAnonymousClassValue(ReflectionClass $valueClass): ReflectionType
    {
        $result = [];
        if (($parentClass = $valueClass->getParentClass()) !== false) {
            $result[] = $parentClass->getName();
        }
        foreach ($valueClass->getInterfaceNames() as $interfaceName) {
            $result[] = $interfaceName;
        }
        return $result === [] ? self::objectReflectionType() : self::buildReflectionType(self::unionTypeMembersToCanonicalName($result));
    }

    public static function getReflectionTypeForValue(mixed $value): ?ReflectionType
    {
        Assert::assertNotNull($value);

        if (is_array($value)) {
            return self::arrayReflectionType();
        }
        if (is_bool($value)) {
            return self::boolReflectionType();
        }
        if (is_callable($value)) {
            return self::callableReflectionType();
        }
        if (is_float($value)) {
            return self::floatReflectionType();
        }
        if (is_iterable($value)) {
            return self::iterableReflectionType();
        }
        if (is_int($value)) {
            return self::intReflectionType();
        }
        if (is_object($value)) {
            $valueClass = new ReflectionClass($value);
            if ($valueClass->isAnonymous()) {
                return self::getReflectionTypeForAnonymousClassValue($valueClass);
            }
            return self::buildReflectionType(get_class($value));
        }
        if (is_resource($value)) {
            return null;
        }
        if (is_string($value)) {
            return self::stringReflectionType();
        }

        return null;
    }

    /**
     * @template T
     *
     * @param Closure(T): void $closureWithTypeParam
     */
    public static function extractReflectionTypeFromClosureParam(Closure $closureWithTypeParam): ReflectionType
    {
        if (AmbientContextForTests::isInited()) {
            DebugContext::getCurrentScope(/* out */ $dbgCtx);
        } else {
            $dbgCtx = null;
        }

        $reflParams = (new ReflectionFunction($closureWithTypeParam))->getParameters();
        $dbgCtx?->add(compact('reflParams'));
        $reflParam = ArrayUtilForTests::getSingleValue($reflParams);
        $dbgCtx?->add(compact('reflParam'));
        return AssertEx::notNull($reflParam->getType());
    }

    /**
     * @template T
     *
     * @param Closure(T): void $closureWithTypeParam
     */
    public static function extractReflectionTypeFromClosureParamAssertName(Closure $closureWithTypeParam, string $expectedTypeName): ReflectionType
    {
        if (AmbientContextForTests::isInited()) {
            DebugContext::getCurrentScope(/* out */ $dbgCtx);
        } else {
            $dbgCtx = null;
        }

        $type = self::extractReflectionTypeFromClosureParam($closureWithTypeParam);
        $actualTypeName = $type->__toString();
        $dbgCtx?->add(compact('actualTypeName'));
        Assert::assertTrue(self::areEquivalentReflectionTypeNames($expectedTypeName, $actualTypeName));
        return $type;
    }

    public static function buildReflectionType(string $typeAsString): ReflectionType
    {
        if (AmbientContextForTests::isInited()) {
            DebugContext::getCurrentScope(/* out */ $dbgCtx);
        } else {
            $dbgCtx = null;
        }

        $dummyClosure = AssertEx::opaqueAlwaysZero() === 0 ? null : (fn(int $_) => null);
        $codeToEvalToDefineDummyClosure = '$dummyClosure = (fn(' . $typeAsString . ' $_) => null);';
        $dbgCtx?->add(compact('codeToEvalToDefineDummyClosure'));
        try {
            eval($codeToEvalToDefineDummyClosure);
        } catch (ParseError $parseError) {
            Assert::fail(LoggableToString::convertMessageAndContext('eval () failed', compact('parseError')));
        }
        $dbgCtx?->add(['dummyClosure type' => get_debug_type($dummyClosure)]);
        Assert::assertNotNull($dummyClosure);
        return self::extractReflectionTypeFromClosureParamAssertName($dummyClosure, $typeAsString);
    }

    public static function getNullableReflectionTypeFor(ReflectionType $baseReflType): ReflectionType
    {
        return
            $baseReflType->allowsNull()
                ? $baseReflType
                : self::buildReflectionType($baseReflType instanceof ReflectionUnionType ? ($baseReflType . '|null') : ('?' . $baseReflType));
    }

    public static function arrayReflectionType(): ReflectionType
    {
        /**
         * @param array<mixed> $_
         */
        $closureWithTypeParam = fn(array $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, self::ARRAY_TYPE_NAME);
    }

    public static function nullableArrayReflectionType(): ReflectionType
    {
        /**
         * @param ?array<mixed> $_
         */
        $closureWithTypeParam = fn(?array $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . self::ARRAY_TYPE_NAME);
    }

    public static function boolReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(bool $_) => null, 'bool');
    }

    public static function nullableBoolReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(?bool $_) => null, '?bool');
    }

    public static function callableReflectionType(): ReflectionType
    {
        /**
         * @param callable(): void $_
         */
        $closureWithTypeParam = fn(callable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, self::CALLABLE_TYPE_NAME);
    }

    public static function nullableCallableReflectionType(): ReflectionType
    {
        /**
         * @param ?callable(): void $_
         */
        $closureWithTypeParam = fn(?callable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . self::CALLABLE_TYPE_NAME);
    }

    public static function closureReflectionType(): ReflectionType
    {
        /**
         * @param Closure(): void $_
         */
        $closureWithTypeParam = fn(Closure $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, Closure::class);
    }

    public static function nullableClosureReflectionType(): ReflectionType
    {
        /**
         * @param ?Closure(): void $_
         */
        $closureWithTypeParam = fn(?Closure $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . Closure::class);
    }

    public static function floatReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(float $_) => null, self::FLOAT_TYPE_NAME);
    }

    public static function nullableFloatReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(?float $_) => null, '?' . self::FLOAT_TYPE_NAME);
    }

    public static function generatorReflectionType(): ReflectionType
    {
        /**
         * @param Generator<mixed> $_
         */
        $closureWithTypeParam = fn(Generator $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, Generator::class);
    }

    public static function nullableGeneratorReflectionType(): ReflectionType
    {
        /**
         * @param ?Generator<mixed> $_
         */
        $closureWithTypeParam = fn(?Generator $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . Generator::class);
    }

    public static function intReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(int $_) => null, self::INT_TYPE_NAME);
    }

    public static function nullableIntReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(?int $_) => null, '?' . self::INT_TYPE_NAME);
    }

    public static function iteratorReflectionType(): ReflectionType
    {
        /**
         * @param Iterator<mixed> $_
         */
        $closureWithTypeParam = fn(Iterator $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, Iterator::class);
    }

    public static function nullableIteratorReflectionType(): ReflectionType
    {
        /**
         * @param ?Iterator<mixed> $_
         */
        $closureWithTypeParam = fn(?Iterator $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . Iterator::class);
    }


    public static function iteratorAggregateReflectionType(): ReflectionType
    {
        /**
         * @param IteratorAggregate<mixed> $_
         */
        $closureWithTypeParam = fn(IteratorAggregate $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, IteratorAggregate::class);
    }

    public static function nullableIteratorAggregateReflectionType(): ReflectionType
    {
        /**
         * @param ?IteratorAggregate<mixed> $_
         */
        $closureWithTypeParam = fn(?IteratorAggregate $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . IteratorAggregate::class);
    }

    public static function iterableReflectionType(): ReflectionType
    {
        /**
         * @param iterable<mixed> $_
         */
        $closureWithTypeParam = fn(iterable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, self::ITERABLE_TYPE_NAME);
    }

    public static function nullableIterableReflectionType(): ReflectionType
    {
        /**
         * @phpstan-param ?iterable<mixed> $_
         */
        $closureWithTypeParam = fn(?iterable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . self::ITERABLE_TYPE_NAME);
    }

    public static function mixedReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(mixed $_) => null, self::MIXED_TYPE_NAME);
    }

    public static function objectReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(object $_) => null, self::OBJECT_TYPE_NAME);
    }

    public static function nullableObjectReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(?object $_) => null, '?' . self::OBJECT_TYPE_NAME);
    }

    public static function stringReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(string $_) => null, 'string');
    }

    public static function nullableStringReflectionType(): ReflectionType
    {
        return self::extractReflectionTypeFromClosureParamAssertName(fn(?string $_) => null, '?string');
    }

    public static function traversableReflectionType(): ReflectionType
    {
        /**
         * @param Traversable<mixed> $_
         */
        $closureWithTypeParam = fn(Traversable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, Traversable::class);
    }

    public static function nullableTraversableReflectionType(): ReflectionType
    {
        /**
         * @param ?Traversable<mixed> $_
         */
        $closureWithTypeParam = fn(?Traversable $_) => null;
        return self::extractReflectionTypeFromClosureParamAssertName($closureWithTypeParam, '?' . Traversable::class);
    }
}
