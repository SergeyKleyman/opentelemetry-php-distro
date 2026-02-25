<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use OTelDistroTests\Util\ClassicFormatStackTraceFrame;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\StackTraceUtil;

/**
 * Code in this file is part of implementation internals, and thus it is not covered by the backward compatibility.
 *
 * @internal
 */
final class LoggableStackTrace
{
    public const STACK_TRACE_KEY = 'stacktrace';

    public const MAX_NUMBER_OF_STACK_FRAMES = 100;

    /**
     * @param non-negative-int $numberOfStackFramesToSkip
     * @param ?positive-int    $maxNumberOfStackFrames
     *
     * @return ClassicFormatStackTraceFrame[]
     */
    public static function buildForCurrent(int $numberOfStackFramesToSkip, ?int $maxNumberOfStackFrames = self::MAX_NUMBER_OF_STACK_FRAMES): array
    {
        $capturedFrames = (new StackTraceUtil(NoopLoggerFactory::singletonInstance()))->captureInClassicFormat($numberOfStackFramesToSkip + 1, $maxNumberOfStackFrames);
        /** @var ClassicFormatStackTraceFrame[] $result */
        $result = [];

        foreach ($capturedFrames as $capturedFrame) {
            $result[] = new ClassicFormatStackTraceFrame(
                self::adaptSourceCodeFilePath($capturedFrame->file),
                $capturedFrame->line,
                ($capturedFrame->class === null) ? null : ClassNameUtil::fqToShortFromRawString($capturedFrame->class),
                $capturedFrame->isStaticMethod,
                $capturedFrame->function
            );
        }
        return $result;
    }

    public static function adaptSourceCodeFilePath(?string $srcFile): ?string
    {
        return $srcFile === null ? null : basename($srcFile);
    }
}
