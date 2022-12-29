<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use Stringable;
use DateTimeImmutable;
use DateTimeZone;

abstract class RecordFactory
{
    public abstract function create($level, string|Stringable $message, array $context = []): Record;
    public static function default(string $channel = '', array $extra = []): RecordFactory
    {
        return new class($channel, $extra) extends RecordFactory
        {
            public function __construct(private string $channel = '', private array $extra = [])
            {
            }
            public function create($level, string|Stringable $message, array $context = []): Record
            {
                return new Record(
                    new DateTimeImmutable('now', new DateTimeZone('UTC')),
                    $this->channel,
                    Level::fromName($level),
                    strval($message),
                    $context,
                    $this->extra
                );
            }
        };
    }
    public static function caller_channel(array $extra = []) : RecordFactory{
        return new class($extra) extends RecordFactory
        {
            public function __construct(private array $extra = [])
            {
            }
            public function create($level, string|Stringable $message, array $context = []): Record
            {
                $channel = self::find_channel(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,4));
                return new Record(
                    new DateTimeImmutable('now', new DateTimeZone('UTC')),
                    $channel,
                    Level::fromName($level),
                    strval($message),
                    $context,
                    $this->extra
                );
            }
            private static function find_channel($dbg){

                if(isset($dbg[3])){
                    if(isset($dbg[3]['class'])){
                        return $dbg[3]['class'];
                    }
                }
                return 'NONE';
        
            }
        };
    }
}
