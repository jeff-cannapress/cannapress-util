<?php

declare(strict_types=1);

namespace CannaPress\Util;

use ArrayAccess;
use Iterator;

class Env implements ArrayAccess, Iterator
{

    private function __construct(private string $prefix, private array $vars)
    {
    }
    public static function create(string $source_file)
    {
        return new self('', array_merge($_ENV, self::read_source_file($source_file)));
    }
    public function create_child(...$segments)
    {
        $prefix = ltrim($this->prefix . ':' . implode(':', $segments), ':');
        return new self($prefix, $this->vars);
    }

    private static function read_source_file_uncached($source_file)
    {
        if (!is_readable($source_file)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $source_file));
        }
        $result = (object)['filemtime' => filemtime($source_file), 'lines' => []];

        $lines = file($source_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(ltrim($line), '#') === 0) {
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
            $cache_key = 'cpscache:' . hash('xxh128', $source_file);
            $stamp = filemtime($source_file);
            $cached_value = get_transient($cache_key);
            if ($cached_value === false || $cached_value->filemtime !== $stamp) {
                $cached_value = self::read_source_file_uncached($source_file);
                set_transient($cache_key, $cached_value);
            }
            return $cached_value->lines;
        }
        return [];
    }
    public function get(string $name, ?string $default = null, bool $force_scoped = false)
    {
        $scoped = ltrim($this->prefix . ':' . $name, ':');
        if (isset($this->vars[$scoped])) {
            return $this->vars[$scoped];
        } else if ($force_scoped) {
            return $default;
        }
        return isset($this->vars[$name]) ? $this->vars[$name] : $default;
    }
    public function __get($name)
    {
        return $this->get($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->vars[$offset]);
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->vars[$offset] : null;
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->vars[$offset] = $value;
    }
    public function offsetUnset(mixed $offset): void
    {
        unset($this->vars[$offset]);
    }
    public function rewind(): void
    {
        reset($this->vars);
    }

    public function current()
    {
        return current($this->vars);
    }
    function key()
    {
        return key($this->vars);
    }
    public function next(): void
    {
        next($this->vars);
    }
    public function valid(): bool
    {
        return key($this->vars) !== null;
    }
}
