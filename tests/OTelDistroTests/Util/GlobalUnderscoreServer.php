<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use OpenTelemetry\Distro\Util\ArrayUtil;
use OpenTelemetry\Distro\Util\StaticClassTrait;
use OpenTelemetry\Distro\Util\TextUtil;
use PHPUnit\Framework\Assert;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class GlobalUnderscoreServer
{
    use StaticClassTrait;

    private const HTTP_REQUEST_HEADER_KEY_PREFIX = 'HTTP_';

    private static function getValue(string $key): mixed
    {
        Assert::assertArrayHasKey($key, $_SERVER);
        return $_SERVER[$key];
    }

    public static function requestMethod(): string
    {
        return AssertEx::isString(self::getValue('REQUEST_METHOD'));
    }

    public static function requestUri(): string
    {
        return AssertEx::isString(self::getValue('REQUEST_URI'));
    }

    public static function getRequestHeaderValue(string $headerName): ?string
    {
        if (ArrayUtil::getValueIfKeyExists(self::HTTP_REQUEST_HEADER_KEY_PREFIX . strtoupper($headerName), $_SERVER, /* out */ $headerValue)) {
            Assert::assertIsString($headerValue);
            return $headerValue;
        }
        return null;
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function getAll(): iterable
    {
        foreach ($_SERVER as $key => $value) {
            yield $key => $value;
        }
    }

    /**
     * @return iterable<string, mixed>
     *
     * @noinspection PhpUnused
     */
    public static function getAllRequestHeaders(): iterable
    {
        $prefixLen = strlen(self::HTTP_REQUEST_HEADER_KEY_PREFIX);
        foreach ($_SERVER as $key => $value) {
            if (TextUtil::isPrefixOf(self::HTTP_REQUEST_HEADER_KEY_PREFIX, $key)) {
                yield substr($key, $prefixLen) => $value;
            }
        }
    }
}
