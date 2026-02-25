<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\SingletonInstanceTrait;
use OTelDistroTests\Util\LimitedSizeCache;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @phpstan-type ConverterToLog callable(object): mixed
 * @phpstan-type FinderConverterToLog callable(object): ?ConverterToLog
 */
final class LogExternalClassesRegistry
{
    use SingletonInstanceTrait;

    /** @var FinderConverterToLog[] */
    private array $findersConverterToLog = [];

    /** @var LimitedSizeCache<class-string<object>, ?ConverterToLog> */
    private LimitedSizeCache $classNameToConverterCache;

    private const CACHE_COUNT_LOW_WATER_MARK = 10000;
    private const CACHE_COUNT_HIGH_WATER_MARK = 2 * self::CACHE_COUNT_LOW_WATER_MARK;

    private function __construct()
    {
        $this->classNameToConverterCache = new LimitedSizeCache(countLowWaterMark: self::CACHE_COUNT_LOW_WATER_MARK, countHighWaterMark: self::CACHE_COUNT_HIGH_WATER_MARK);
    }

    /**
     * @param FinderConverterToLog $finderConverterToLog
     */
    public function addFinder(callable $finderConverterToLog): void
    {
        self::singletonInstance()->findersConverterToLog[] = $finderConverterToLog;
    }

    /**
     * @param object $object
     *
     * @return ?ConverterToLog
     */
    public function finderConverterToLog(object $object): ?callable
    {
        /**
         * @return ?ConverterToLog
         */
        $queryFinders = function () use ($object): ?callable {
            foreach ($this->findersConverterToLog as $finder) {
                if (($converter = $finder($object)) !== null) {
                    return $converter;
                }
            }
            return null;
        };

        return $this->classNameToConverterCache->getIfCachedElseCompute(get_class($object), $queryFinders);
    }
}
