<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use OTelDistroTests\Util\Log\LoggableInterface;
use OTelDistroTests\Util\Log\LogStreamInterface;
use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
enum AppCodeHostKind: string implements LoggableInterface
{
    case cliScript = 'CLI_script';
    case builtinHttpServer = 'Builtin_HTTP_server';

    public function isHttp(): bool
    {
        return match ($this) {
            self::cliScript => false,
            self::builtinHttpServer => true,
        };
    }

    #[Override]
    public function toLog(LogStreamInterface $stream): void
    {
        $stream->toLogAs($this->value);
    }
}
