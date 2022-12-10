<?php

//declare(strict_types=1);

namespace CannaPress\Util;

class Hashes
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
}
