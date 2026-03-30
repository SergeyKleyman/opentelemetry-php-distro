<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace OpenTelemetry\Distro\Util;

final class ListUtil
{
    /**
     * @template T
     *
     * @param list<T> $from
     * @param list<T> $to
     */
    public static function append(array $from, /* in,out */ array &$to): void
    {
        $to = array_merge($to, $from);
    }

    /**
     * @template T
     *
     * @param list<T> $list1
     * @param list<T> $list2
     * @param list<T> ...$moreLists
     *
     * @return list<T>
     */
    public static function concat(array $list1, array $list2, array ...$moreLists): array
    {
        $result = [];
        self::append($list1, /* ref */ $result);
        self::append($list2, /* ref */ $result);
        foreach ($moreLists as $listToAppend) {
            self::append($listToAppend, /* ref */ $result);
        }
        return $result;
    }
}
