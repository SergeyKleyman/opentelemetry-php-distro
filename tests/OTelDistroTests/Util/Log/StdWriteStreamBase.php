<?php

declare(strict_types=1);

namespace OTelDistroTests\Util\Log;

abstract class StdWriteStreamBase implements LoggableInterface
{
    use LoggableTrait;

    private ?bool $isDefined = null;

    /** @var ?resource */
    private $stream = null;

    public function __construct(
        private readonly string $streamName
    ) {
    }

    private function globalConstantName(): string
    {
        return strtoupper($this->streamName);
    }

    /**
     * @return bool
     *
     * @phpstan-assert-if-true !null $this->stream
     */
    private function ensureIsDefined(): bool
    {
        $globalConstantName = $this->globalConstantName();
        if ($this->isDefined === null) {
            if (defined(strtoupper($this->streamName))) {
                $this->isDefined = true;
            } else {
                define($globalConstantName, fopen('php://' . $this->streamName, 'w'));
                $this->isDefined = defined($globalConstantName);
            }
        }

        if ($this->isDefined) {
            $globalConstantValue = constant($this->globalConstantName());
            if (is_resource($globalConstantValue)) {
                $this->stream = $globalConstantValue;
            } else {
                $this->isDefined = false;
            }
        }

        return $this->isDefined;
    }

    public function getStream(): mixed
    {
        return self::ensureIsDefined() ? $this->stream : null;
    }

    public function writeLine(string $text): void
    {
        if ($this->ensureIsDefined()) {
            fwrite($this->stream, $text . PHP_EOL);
            fflush($this->stream);
        }
    }
}
