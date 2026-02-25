<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\BoolUtilForTests;

final class DbAutoInstrumentationUtilForTests
{
    use StaticClassTrait;

    public const HOST_KEY = 'HOST';
    public const PORT_KEY = 'PORT';
    public const USER_KEY = 'USER';
    public const PASSWORD_KEY = 'PASSWORD';

    public const DB_NAME_KEY = 'db_name';
    public const USE_SELECT_DB_KEY = 'use_select_db';
    public const WRAP_IN_TX_KEY = 'wrap_in_TX';
    public const SHOULD_ROLLBACK_KEY = 'should_rollback';

    /**
     * @return callable(array<mixed>): iterable<array<mixed>>
     */
    public static function wrapTxRelatedArgsDataProviderGenerator(): callable
    {
        /**
         * @param array<mixed> $resultSoFar
         *
         * @return iterable<array<mixed>>
         */
        return function (array $resultSoFar): iterable {
            foreach (BoolUtilForTests::ALL_VALUES as $wrapInTx) {
                $rollbackValues = $wrapInTx ? [false, true] : [false];
                foreach ($rollbackValues as $rollback) {
                    yield array_merge(
                        $resultSoFar,
                        [
                            self::WRAP_IN_TX_KEY      => $wrapInTx,
                            self::SHOULD_ROLLBACK_KEY => $rollback,
                        ]
                    );
                }
            }
        };
    }
}
