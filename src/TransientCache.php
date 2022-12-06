<?php

declare(strict_types=1);

namespace CannaPress\Util;

class TransientCache
{
    private array $call_cache = [];
    public function __construct(private string $prefix = '')
    {
    }
    private function make_transient_key($key)
    {
        return $this->prefix . \CannaPress\Util\Hashes::fast($key);
    }
    public function child($prefix_part)
    {
        return new TransientCache($this->prefix . ':' . $prefix_part);
    }
    public function remember($key, callable $factory, $expiration = 0)
    {
        if (!isset($this->call_cache[$key])) {
            $transient_key = $this->make_transient_key($key);
            $value = self::get_transient($transient_key);
            if ($value === false) {
                $value = $factory();
            }
            $this->call_cache[$key] = $value;
            self::set_transient($transient_key, $value, $expiration);
        }
        return isset($this->call_cache[$key]) ? $this->call_cache[$key] : null;
    }
    public function get($key): mixed
    {
        if (!isset($this->call_cache[$key])) {
            $value = self::get_transient($this->make_transient_key($key));
            if ($value !== false) {
                $this->call_cache[$key] = $value;
            }
        }
        return isset($this->call_cache[$key]) ? $this->call_cache[$key] : null;
    }
    public function set($key, $value, int $expiration = 0): bool
    {
        $was_set = self::set_transient($this->make_transient_key($key), $value, $expiration);
        if ($was_set) {
            $this->call_cache[$key] = $value;
        }
        return $was_set;
    }
    private static $in_proc_fallback = [];
    public static function get_transient(string $transient): mixed
    {
        if (function_exists('get_transient')) {
            return get_transient($transient);
        }
        if (isset(self::$in_proc_fallback[$transient])) {
            return self::$in_proc_fallback[$transient];
        }
        return false;
    }
    public static function set_transient(string $transient, mixed $value, int $expiration = 0): bool
    {
        if (function_exists('set_transient')) {
            return set_transient($transient, $value, $expiration);
        }
        self::$in_proc_fallback[$transient] = $value;
        return true;
    }
}
