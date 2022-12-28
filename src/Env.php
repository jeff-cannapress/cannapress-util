<?php

declare(strict_types=1);

namespace CannaPress\Util;


class Env
{

    private function __construct(private array $vars)
    {
    }
    public static function create(string $source_file)
    {
        return new self(array_merge($_ENV, self::read_source_file($source_file)));
    }

    private static function read_source_file_uncached($source_file)
    {
        if (!is_readable($source_file)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $source_file));
        }
        $result = (object)['filemtime' => filemtime($source_file), 'lines' => []];

        $lines = file($source_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = ltrim($line);
            if (strpos($line, '#') === 0) {
                continue;
            }
            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $result->lines[$name] = $value;
            } else {
                $result->lines[$line] = true;
            }
        }
        return $result;
    }
    private static function read_source_file($source_file)
    {
        if (file_exists($source_file)) {
            $cache_key = 'cpscache:' . \CannaPress\Util\Hashes::fast($source_file);
            $stamp = filemtime($source_file);
            $cached_value = \CannaPress\Util\TransientCache::get_transient($cache_key);
            if ($cached_value === false || $cached_value->filemtime !== $stamp) {
                $cached_value = self::read_source_file_uncached($source_file);
                \CannaPress\Util\TransientCache::set_transient($cache_key, $cached_value);
            }
            return $cached_value->lines;
        }
        return [];
    }
    public function get(string $name, ?string $default = null)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : $default;
    }
}
