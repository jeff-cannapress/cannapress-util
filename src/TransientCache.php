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
        return $this->prefix . hash('xxh128', $key);
    }
    public function child($prefix_part)
    {
        return new TransientCache($this->prefix . ':' . $prefix_part);
    }
    public function remember($key, callable $factory, $expiration = 0)
    {
        if (!isset($this->call_cache[$key])) {
            $transient_key = $this->make_transient_key($key);
            $value = false;
            if (function_exists('get_transient')) {
                $value = get_transient($transient_key);
            }
            if ($value === false) {
                $value = $factory();
            }
            $this->call_cache[$key] = $value;
            if (function_exists('set_transient')) {
                set_transient($transient_key, $value, $expiration);
            }
        }
        return isset($this->call_cache[$key]) ? $this->call_cache[$key] : null;
    }
    public function get($key): mixed
    {
        if (!isset($this->call_cache[$key])) {
            $value = false;
            if (function_exists('get_transient')) {
                $transient_key = $this->make_transient_key($key);
                $value = get_transient($transient_key);
            }
            if ($value !== false) {
                $this->call_cache[$key] = $value;
            }
        }
        return isset($this->call_cache[$key]) ? $this->call_cache[$key] : null;
    }
    public function set($key, $value, int $expiration = 0): bool
    {
        $was_set = true;
        if (function_exists('set_transient')) {
            $transient_key = $this->make_transient_key($key);
            set_transient($transient_key, $value, $expiration);
        }
        if ($was_set) {
            $this->call_cache[$key] = $value;
        }
        return $was_set;
    }
}
