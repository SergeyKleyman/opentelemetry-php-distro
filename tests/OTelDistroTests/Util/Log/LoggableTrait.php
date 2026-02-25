<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use ReflectionClass;
use ReflectionException;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
trait LoggableTrait
{
    protected static function classNameToLog(): ?string
    {
        return null;
    }

    /**
     * @return string[]
     */
    protected static function defaultPropertiesExcludedFromLog(): array
    {
        return ['logger'];
    }

    /**
     * @return string[]
     */
    protected static function propertiesExcludedFromLog(): array
    {
        return [];
    }

    /**
     * @param LogStreamInterface   $stream
     * @param array<string, mixed> $customPropValues
     */
    protected function toLogLoggableTraitImpl(LogStreamInterface $stream, array $customPropValues = []): void
    {
        $nameToValue = $customPropValues;

        $classNameToLog = static::classNameToLog();
        if ($classNameToLog !== null) {
            $nameToValue[LogConsts::TYPE_KEY] = $classNameToLog;
        }

        try {
            $currentClass = new ReflectionClass(get_class($this));
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ReflectionException $ex) {
            $stream->toLogAs(LoggingSubsystem::onInternalFailure('Failed to reflect', ['class' => get_class($this)], $ex));
            return;
        }

        $propertiesExcludedFromLog = array_merge(static::propertiesExcludedFromLog(), static::defaultPropertiesExcludedFromLog());
        while (true) {
            foreach ($currentClass->getProperties() as $reflectionProperty) {
                if ($reflectionProperty->isStatic()) {
                    continue;
                }

                $propName = $reflectionProperty->name;
                if (array_key_exists($propName, $customPropValues)) {
                    continue;
                }
                if (in_array($propName, $propertiesExcludedFromLog, strict: true)) {
                    continue;
                }
                $nameToValue[$propName] = $reflectionProperty->isInitialized($this) ? $reflectionProperty->getValue($this) : LogConsts::UNINITIALIZED_PROPERTY_SUBSTITUTE;
            }
            $currentClass = $currentClass->getParentClass();
            if ($currentClass === false) {
                break;
            }
        }

        $stream->toLogAs($nameToValue);
    }

    public function toLog(LogStreamInterface $stream): void
    {
        $this->toLogLoggableTraitImpl($stream);
    }
}
