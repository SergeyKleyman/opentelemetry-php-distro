<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Closure;
use OTelDistroTests\Util\Config\RawSnapshotFromArray;
use OTelDistroTests\Util\Config\RawSnapshotInterface;
use OTelDistroTests\Util\Config\RawSnapshotSourceInterface;
use Override;

/**
 * @phpstan-type GetHeader Closure(string): ?string
 */
final class RequestHeadersRawSnapshotSource implements RawSnapshotSourceInterface
{
    public const HEADER_NAMES_PREFIX = 'OTEL_PHP_TESTS_';

    /** @var GetHeader */
    private Closure $getHeaderValue;

    /**
     * @param GetHeader $getHeaderValue
     */
    public function __construct(Closure $getHeaderValue)
    {
        $this->getHeaderValue = $getHeaderValue;
    }

    public static function optionNameToHeaderName(string $optName): string
    {
        return self::HEADER_NAMES_PREFIX . strtoupper($optName);
    }

    /** @inheritDoc */
    #[Override]
    public function currentSnapshot(array $optionNameToMeta): RawSnapshotInterface
    {
        /** @var array<string, string> $optionNameToHeaderValue */
        $optionNameToHeaderValue = [];

        foreach ($optionNameToMeta as $optionName => $optionMeta) {
            $headerValue = ($this->getHeaderValue)(self::optionNameToHeaderName($optionName));
            if ($headerValue !== null) {
                $optionNameToHeaderValue[$optionName] = $headerValue;
            }
        }

        return new RawSnapshotFromArray($optionNameToHeaderValue);
    }
}
