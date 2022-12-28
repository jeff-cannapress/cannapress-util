<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use SplFileObject;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use Stringable;
use Psr\Log\AbstractLogger;

class DefaultLogger extends AbstractLogger
{
    private ?SplFileObject $file = null;
    public function __construct(private string $path)
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

    public function log($level, string|Stringable $message, array $context = []): void
    {
        try{
            $record = $this->build_record($level, $message, $context);
            $file = $this->get_file();
            $file->fwrite($record);
        }
        catch(Throwable $ex){
            //snarf exception;
        }
    }
    private const DEFAULT_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
    private static function build_record($level, string|Stringable $message, array $context = [])
    {
        $output = '[' . strtoupper($level) . '/' . (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ISO8601) . ']:' . strval($message);
        $ctx = array_merge($context);
        foreach ($ctx as $var => $val) {
            if (false !== strpos($output, '%context.' . $var . '%')) {
                $output = str_replace('%context.' . $var . '%', self::to_string($val), $output);
                unset($ctx[$var]);
            }
        }
        if (!empty($ctx)) {
            $output .= ' ' . var_export($ctx, true);
        }
        return $output."\n";
    }
    /**
     * @param mixed $data
     */
    protected static function to_string($data): string
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }
        if (is_scalar($data)) {
            return (string) $data;
        }
        if (is_object($data) && $data instanceof Throwable) {
            return json_encode(self::objectify_exception($data), self::DEFAULT_JSON_FLAGS);
        }
        return json_encode($data, self::DEFAULT_JSON_FLAGS);
    }


    private static function objectify_exception(Throwable|null $e): object
    {
        if ($e !== null) {
            return (object)[
                'type' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'at' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTrace(),
                'previous' => self::objectify_exception($e->getPrevious())
            ];
        }
    }
}
