<?php

declare(strict_types=1);

namespace CannaPress\Util\Json;

use DateTimeImmutable;


abstract class MetaInfo
{

    public abstract function load(object $instance, array $json): void;

    public abstract function serialize(array &$result, $instance): void;

    public static function datetime(string $prop, ?string $json_prop = null)
    {
        return self::prop(
            $prop,
            null,
            fn ($x) => !empty($x) ? DateTimeImmutable::createFromFormat(DateTimeImmutable::ISO8601, $x) : null,
            fn ($x) => $x?->format(DateTimeImmutable::ISO8601),
            $json_prop
        );
    }
    public static function instance(string $prop, string $clazz, callable|null $default = null,  ?string $json_prop = null)
    {
        if (is_null($json_prop)) {
            $json_prop = $prop;
        }
        if (is_null($default)) {
            $default = fn () => new $clazz();
        }

        return new class($prop, $clazz, $default, $json_prop) extends MetaInfo
        {
            public function __construct(private string $prop, private string $clazz, private $default,  private string $json_prop)
            {
            }

            public function load(object $instance, array|object $json): void
            {
                $value = isset($json[$this->json_prop]) ? $json[$this->json_prop] : null;
                try {
                    $to_assign = ($this->default)();
                    if (!is_null($to_assign)) {
                        call_user_func([$this->clazz, 'loadInstance'], $to_assign, $value);
                    }
                    $instance->{$this->prop} = $to_assign;
                } catch (\Exception $err) {
                    var_dump($err);
                }
            }
            public function serialize(array &$result, $instance): void
            {
                $value = $instance->{$this->prop};
                $result[$this->json_prop] = $value?->jsonSerialize();
            }
        };
    }
    public static function prop(string $prop, mixed $default = null, callable $coerce_load = null, callable $coerce_save = null, ?string $json_prop = null): MetaInfo
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
        if (is_null($json_prop)) {
            $json_prop = $prop;
        }
        return new class($prop, $default, $coerce_load, $coerce_save, $json_prop) extends MetaInfo
        {
            public function __construct(private string $prop, private  $default, private $coerce_load, private $coerce_save, private string $json_prop)
            {
            }
            public function key(): string
            {
                return $this->json_prop;
            }
            public function load(object $instance, array|object $json): void
            {
                if(is_object($json)){
                    $json= get_object_vars($json);
                }
                $value = isset($json[$this->json_prop]) ? $json[$this->json_prop] : ($this->default)();
                $value = ($this->coerce_load)($value);
                $instance->{$this->prop} = $value;
            }
            public function serialize(array &$result, $instance): void
            {
                $value = $instance->{$this->prop};
                $result[$this->json_prop] = ($this->coerce_save)($value);
            }
        };
    }

    public static function loadProps(object $instance, $json): object
    {
        if (is_object($instance)) {
            if (is_string($json)) {
                $json = json_decode($json);
            }
            if (is_object($json)) {
                $json = get_object_vars($json);
            }
            foreach ($json as $key => $value) {
                $instance->{$key} = $value;
            }
        }
        return $instance;
    }
}
