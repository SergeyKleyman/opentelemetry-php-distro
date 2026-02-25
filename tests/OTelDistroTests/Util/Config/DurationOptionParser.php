<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Config;

use OpenTelemetry\Distro\Util\TextUtil;
use OTelDistroTests\Util\Duration;
use OTelDistroTests\Util\DurationUnit;
use OTelDistroTests\Util\ExceptionUtil;
use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 *
 * @extends OptionParser<Duration>
 */
final class DurationOptionParser extends OptionParser
{
    public function __construct(
        public readonly ?Duration $minValidValue,
        public readonly ?Duration $maxValidValue,
        public readonly DurationUnit $defaultUnits
    ) {
    }

    /** @inheritDoc */
    #[Override]
    public function parse(string $rawValue): Duration
    {
        $partWithoutSuffix = '';
        $units = $this->defaultUnits;
        self::splitToValueAndUnits($rawValue, /* ref */ $partWithoutSuffix, /* ref */ $units);

        $auxFloatOptionParser = new FloatOptionParser(null /* minValidValue */, null /* maxValidValue */);
        $parsedValue = new Duration($auxFloatOptionParser->parse($partWithoutSuffix), $units);

        if (
            (($this->minValidValue !== null) && ($this->minValidValue->compare($parsedValue) > 0))
            ||
            (($this->maxValidValue !== null) && ($this->maxValidValue->compare($parsedValue) < 0))
        ) {
            throw new ParseException(
                ExceptionUtil::buildMessage(
                    'Value is not in range between the valid minimum and maximum values',
                    array_merge(compact('rawValue', 'parsedValue'), ['minValidValue' => $this->minValidValue, 'maxValidValue' => $this->maxValidValue])
                )
            );
        }

        return $parsedValue;
    }

    private static function splitToValueAndUnits(string $rawValue, string &$partWithoutSuffix, DurationUnit &$units): void
    {
        foreach (DurationUnit::cases() as $durationUnit) {
            $suffix = $durationUnit->name;
            if (TextUtil::isSuffixOf($suffix, $rawValue, isCaseSensitive: false)) {
                $partWithoutSuffix = trim(substr($rawValue, 0, -strlen($suffix)));
                $units = $durationUnit;
                return;
            }
        }
        $partWithoutSuffix = $rawValue;
    }
}
