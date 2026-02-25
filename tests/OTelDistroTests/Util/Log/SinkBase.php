<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OpenTelemetry\Distro\Log\LogLevel;
use OpenTelemetry\Distro\Util\TextUtil;
use Override;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
abstract class SinkBase implements SinkInterface
{
    /** @inheritDoc */
    #[Override]
    public function consume(
        LogLevel $statementLevel,
        string $message,
        array $context,
        string $category,
        string $srcCodeFile,
        int $srcCodeLine,
        string $srcCodeFunc,
        ?bool $includeStacktrace,
        int $numberOfStackFramesToSkip
    ): void {
        if ($includeStacktrace === null ? ($statementLevel <= LogLevel::error) : $includeStacktrace) {
            $context[LoggableStackTrace::STACK_TRACE_KEY] = LoggableStackTrace::buildForCurrent($numberOfStackFramesToSkip + 1);
        }

        $ctxAsStr = LoggableToString::convert($context);
        $msgCtxSeparator = (TextUtil::isEmptyString($message) || TextUtil::isEmptyString($ctxAsStr)) ? '' : ' ';
        $messageWithContext = $message . $msgCtxSeparator . $ctxAsStr;

        $this->consumePreformatted(
            $statementLevel,
            $category,
            $srcCodeFile,
            $srcCodeLine,
            $srcCodeFunc,
            $messageWithContext
        );
    }

    abstract protected function consumePreformatted(
        LogLevel $statementLevel,
        string $category,
        string $srcCodeFile,
        int $srcCodeLine,
        string $srcCodeFunc,
        string $messageWithContext
    ): void;
}
