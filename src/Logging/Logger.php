<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use Stringable;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class Logger extends AbstractLogger
{
    public function __construct(private Appender $appender, private RecordFactory $recordFactory = null)
    {
        if ($this->recordFactory == null) {
            $this->recordFactory = RecordFactory::default();
        }
    }
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $record = $this->recordFactory->create($level, $message, $context);
        $this->appender->append($record);
    }
    public static function default(string $path): LoggerInterface
    {
        return new Logger(Appender::file($path, Formatter::flat_file(), RecordFactory::caller_channel()));
    }
}
