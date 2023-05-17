<?php

declare(strict_types=1);

namespace CannaPress\Util;

final class Hashes
{
    private static $fast_algo = null;

    public static function fast($data, bool $binary = false)
    {
        if (is_null(self::$fast_algo)) {
            $supported = hash_algos();
            $requested = ['xxh128', 'fnv1a64', 'md5'];
            $i = 0;
            while (is_null(self::$fast_algo) && $i < count($requested)) {
                if (in_array($requested[$i], $supported)) {
                    self::$fast_algo = $requested[$i];
                }
                $i++;
            }
        }
        return hash(self::$fast_algo, $data, $binary);
    }
    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
    public static function sequential_uuid(): string
    {
        $micros_since_2020 =  floor((microtime(true) - 15778368000000000));/* unix timestamp (micros) of 2020-01-01 UTC */
        return sprintf(
            '%016x%04x%04x%04x%04x',
            $micros_since_2020,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
