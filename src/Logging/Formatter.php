<?php

declare(strict_types=1);

namespace CannaPress\Util\Logging;

use DateTimeImmutable;
use Throwable;

abstract class Formatter
{
    public abstract function format(Record $record): string;
    public static function flat_file(string $delim = "\n"): Formatter
    {
        return new class($delim) extends Formatter
        {
            public function __construct(private string $delim)
            {
            }
            private const DEFAULT_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
            public function format(Record $record): string
            {
                $output = '[' . Level::getName($record->level) . '@' . $record->datetime->format(DateTimeImmutable::ATOM) . (empty($record->channel ? '' : $record->channel)) . ']:' . strval($record->message);
                $ctx = array_merge($record->context);
                foreach ($ctx as $var => $val) {
                    if (false !== strpos($output, '%context.' . $var . '%')) {
                        $output = str_replace('%context.' . $var . '%', self::to_string($val), $output);
                        unset($ctx[$var]);
                    }
                }
                if (!empty($ctx)) {
                    $output .= ' ' . var_export($ctx, true);
                }
                return $output . $this->delim;
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
        };
    }
}
