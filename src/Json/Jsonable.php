<?php

declare(strict_types=1);

namespace CannaPress\Util\Json;

trait Jsonable
{

    protected static abstract function json_metas(): iterable;
    public static function loadInstance($instance, $json)
    {
        if (!isset($json) || empty($json) || is_null($json)) {
            return false;
        }
        if (is_string($json)) {
            $json = json_decode($json, true);
        }
        if ($json) {
            foreach (self::json_metas() as  $meta) {
                $meta->load($instance, $json);
            }
        }
        return $instance;
    }

    public static function jsonDeserialize($json)
    {
        return self::loadInstance(new self(), $json);
    }
    public function __toString()
    {
        return json_encode($this);
    }

    public function jsonSerialize(): mixed
    {
        $result = [];
        foreach (self::json_metas() as  $meta) {
            $result[$meta->key()] = $meta->serialize($this);
        }
        return $result;
    }
}
