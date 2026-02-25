<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Util\StaticClassTrait;
use OTelDistroTests\Util\JsonUtil;
use Exception;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class LoggableToEncodedJson
{
    use StaticClassTrait;

    /**
     * @param mixed $value
     * @param bool  $prettyPrint
     * @param int   $lengthLimit
     *
     * @return string
     */
    public static function convert(
        mixed $value,
        bool $prettyPrint = false,
        /** @noinspection PhpUnusedParameterInspection */ int $lengthLimit = LoggableToString::DEFAULT_LENGTH_LIMIT
    ): string {
        try {
            $jsonEncodable = LoggableToJsonEncodable::convert($value, /* depth: */ 0);
        } catch (Exception $ex) {
            return LoggingSubsystem::onInternalFailure(
                'LoggableToJsonEncodable::convert() failed',
                ['value type' => get_debug_type($value)],
                $ex
            );
        }

        try {
            return JsonUtil::encode($jsonEncodable, $prettyPrint);
        } catch (Exception $ex) {
            return LoggingSubsystem::onInternalFailure(
                'JsonUtil::encode() failed',
                ['$jsonEncodable type' => get_debug_type($jsonEncodable)],
                $ex
            );
        }
    }
}
