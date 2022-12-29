<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use SplFileObject;

abstract class Appender
{
    public abstract function append(Record $record): void;
    public static function file(string $path, Formatter $formatter): Appender
    {
        return new class($path, $formatter) extends Appender
        {
            private ?SplFileObject $file = null;
            public function __construct(private string $path, private Formatter $formatter)
            {
            }
            private static function ensure_dir(string $dir)
            {
                if (!file_exists($dir)) {
                    self::ensure_dir(dirname($dir));
                    mkdir($dir, 0777, true);
                }
            }

            private function get_file(): SplFileObject
            {
                if ($this->file === null) {
                    self::ensure_dir(dirname($this->path));
                    $this->file = new SplFileObject($this->path, 'a+');
                }
                return $this->file;
            }
            public function append(Record $record): void
            {
                $file = $this->get_file();
                $file->fwrite($this->formatter->format($record));
                $file->fflush();
            }
        };
    }
    public static function filter(callable $predicate, Appender $appender): Appender
    {
        return new class($predicate, $appender) extends Appender
        {
            public function __construct(private $predicate, private Appender $appender)
            {
            }
            public function append(Record $record): void
            {
                if (($this->predicate)($record)) {
                    $this->appender->append($record);
                }
            }
        };
    }
    public static function aggregate(...$appenders): Appender
    {
        return new class($appenders) extends Appender
        {
            public function __construct(private $appenders)
            {
            }
            public function append(Record $record): void
            {
                foreach ($this->appenders as $appender) {
                    $appender->append($record);
                }
            }
        };
    }
}
