<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

use DateTime;
use OpenTelemetry\Distro\Log\LogLevel;
use OTelDistroTests\Util\TextUtilForTests;

final class SinkForTests extends SinkBase
{
    public function __construct(
        private readonly string $dbgProcessName
    ) {
    }

    protected function consumePreformatted(
        LogLevel $statementLevel,
        string $category,
        string $srcCodeFile,
        int $srcCodeLine,
        string $srcCodeFunc,
        string $messageWithContext
    ): void {
        $formattedRecord = '[OTel PHP Distro tests]';
        $formattedRecord .= ' ' . (new DateTime())->format('Y-m-d H:i:s.v P');
        $formattedRecord .= ' [' . strtoupper($statementLevel->name) . ']';
        $formattedRecord .= ' [PID: ' . getmypid() . ']';
        $formattedRecord .= ' [' . $this->dbgProcessName . ']';
        $formattedRecord .= ' [' . basename($srcCodeFile) . ':' . $srcCodeLine . ']';
        $formattedRecord .= ' [' . $srcCodeFunc . ']';
        $formattedRecord .= TextUtilForTests::combineWithSeparatorIfNotEmpty(' ', $messageWithContext);
        $this->consumeFormatted($statementLevel, $formattedRecord);
    }

    public static function writeLineToStdErr(string $text): void
    {
        StdError::singletonInstance()->writeLine($text);
    }

    private function consumeFormatted(LogLevel $statementLevel, string $statementText): void
    {
        syslog(self::levelToSyslog($statementLevel), $statementText);
        self::writeLineToStdErr($statementText);
    }

    private static function levelToSyslog(LogLevel $level): int
    {
        return match ($level) {
            LogLevel::off, LogLevel::critical => LOG_CRIT,
            LogLevel::error => LOG_ERR,
            LogLevel::warning => LOG_WARNING,
            LogLevel::info => LOG_INFO,
            LogLevel::debug, LogLevel::trace => LOG_DEBUG
        };
    }
}
