<?php

declare(strict_types=1);

namespace CannaPress\Util\Json;

use DateTimeImmutable;


abstract class MetaInfo
{

    public abstract function load(object $instance, array $json): void;
    public abstract function key(): string;
    public abstract function serialize($instance);

    public static function datetime(string $prop)
    {
        return self::prop(
            $prop,
            null,
            fn ($x) => !empty($x) ? DateTimeImmutable::createFromFormat(DateTimeImmutable::ISO8601, $x) : null,
            fn ($x) => $x?->format(DateTimeImmutable::ISO8601)
        );
    }
    public static function instance(string $prop, string $clazz)
    {
        return new class($prop, $clazz) extends MetaInfo
        {
            public function __construct(private string $prop, private string $clazz)
            {
            }
            public function key(): string
            {
                return $this->prop;
            }
            public function load(object $instance, array $json): void
            {
                $value = isset($json[$this->prop]) ? $json[$this->prop] : null;
                try {
                    $instance->{$this->prop} = call_user_func([$this->clazz, 'loadInstance'], (new ($this->clazz)()), $value);
                } catch (\TypeError $err) {
                }
            }
            public function serialize($instance)
            {
                $value = $instance->{$this->prop};
                if ($value) {
                    return $value->jsonSerialize();
                }
                return null;
            }
        };
    }
    public static function prop(string $prop, mixed $default = null, callable $coerce_load = null, callable $coerce_save = null): MetaInfo
    {
        if (is_null($coerce_load)) {
            $coerce_load = fn ($x) => $x;
        }
        if (is_null($coerce_save)) {
            $coerce_save = fn ($x) => $x;
        }
        if (!is_callable($default)) {
            $value = $default;
            $default = fn () => $value;
        }
        return new class($prop, $default, $coerce_load, $coerce_save) extends MetaInfo
        {
            public function __construct(private string $prop, private  $default, private $coerce_load, private $coerce_save)
            {
            }
            public function key(): string
            {
                return $this->prop;
            }
            public function load(object $instance, array $json): void
            {
                $value = isset($json[$this->prop]) ? $json[$this->prop] : ($this->default)();
                $value = ($this->coerce_load)($value);
                $instance->{$this->prop} = $value;
            }
            public function serialize($instance)
            {
                $value = $instance->{$this->prop};
                return ($this->coerce_save)($value);
            }
        };
    }
}
