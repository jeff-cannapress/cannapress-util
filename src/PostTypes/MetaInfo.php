<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class MetaInfo
{
    private array $props;
    public function __construct(...$props)
    {
        $this->props = $props;
    }
    public static function json_prop(string $name, string $key, bool $associatve = false)
    {
        return new class($name, $key, $associatve)
        {
            public function __construct(private string $prop, private string $meta_key, private bool $associatve)
            {
            }

            public function load(object $the_entity, array $all_metas)
            {
                $strval = MetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);

                if (!empty($strval)) {
                    $the_entity->{$this->prop} = json_decode($strval, $this->associatve);
                }
            }
            public function persist($the_entity)
            {
                update_post_meta($the_entity->ID, $this->meta_key, json_encode($the_entity->{($this->prop)}));
            }
        };
    }
    public static function date_time_zone_prop(string $name, string $key)
    {
        return new class($name, $key)
        {
            public function __construct(private string $prop, private string $meta_key)
            {
            }

            public function load(object $the_entity, array $all_metas)
            {
                $data = MetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);
                if (!empty($data)) {
                    $the_entity->{$this->prop} = new DateTimeZone($data);
                }
            }
            public function persist($the_entity)
            {
                if (!is_null($the_entity->{($this->prop)})) {
                    $val = is_string($the_entity->{($this->prop)})? $the_entity->{($this->prop)} : $the_entity->{($this->prop)}->getName();
                    update_post_meta($the_entity->ID, $this->meta_key, $val);
                } else {
                    delete_post_meta($the_entity->ID, $this->meta_key);
                }
            }
        };
    }
    public static function date_time_prop(string $name, string $key)
    {
        return new class($name, $key)
        {
            public function __construct(private string $prop, private string $meta_key)
            {
            }

            public function load(object $the_entity, array $all_metas)
            {
                $data = MetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);
                $tzval = MetaInfo::get_meta_value($all_metas, $this->meta_key . '_tz', null, false);
                if (!empty($data)) {
                    $tzval = $tzval ?? 'UTC';
                    $the_entity->{$this->prop} = DateTimeImmutable::createFromFormat(DateTimeImmutable::ISO8601, $data)->setTimezone(new DateTimeZone($tzval));
                }
            }
            public function persist($the_entity)
            {
                if (!is_null($the_entity->{($this->prop)})) {
                    update_post_meta($the_entity->ID, $this->meta_key, $the_entity->{($this->prop)}->setTimezone(new DateTimeZone('UTC'))->format(DateTimeImmutable::ISO8601));
                    update_post_meta($the_entity->ID, $this->meta_key . '_tz', $the_entity->{($this->prop)}->getTimezone()->getName());
                } else {
                    delete_post_meta($the_entity->ID, $this->meta_key);
                    delete_post_meta($the_entity->ID, $this->meta_key . '_tz');
                }
            }
        };
    }
    public static function prop($name, $key, $default)
    {
        return new class($name, $key, $default)
        {
            public function __construct(private $prop, private $meta_key, private $default)
            {
            }
            public function coerce_loaded($value)
            {
                if (!is_null($this->default)) {
                    if (is_int($this->default)) {
                        return intval($value);
                    } elseif (is_float($this->default)) {
                        return floatval($value);
                    } elseif (is_bool($this->default)) {
                        return boolval($value);
                    } elseif ($this->default instanceof DateTimeInterface) {
                        return \DateTime::createFromFormat('Y-m-d', $value);
                    }
                }
                return $value;
            }
            public function load(object $the_entity, array $all_metas)
            {
                $strval = MetaInfo::get_meta_value($all_metas, $this->meta_key, $this->default, is_array($this->default));
                $the_entity->{$this->prop} = $this->coerce_loaded($strval);
            }
            public function persist($the_entity)
            {
                $to_save = $this->coerce_saving($the_entity->{($this->prop)});
                update_post_meta($the_entity->ID, $this->meta_key, $to_save);
            }
            public function coerce_saving($value)
            {
                if ($value instanceof DateTimeInterface) {
                    return $value->format('Y-m-d');
                }
                return $value;
            }
        };
    }
    public static function get_meta_value(array $all_metas, $key, $default_value, $multiple = false)
    {
        if (!array_key_exists($key, $all_metas)) {
            return $default_value;
        }
        if ($multiple === false && is_array($all_metas[$key])) {
            return $all_metas[$key][0];
        }
        return $all_metas[$key];
    }

    public function fill($the_entity)
    {
        if ($the_entity->ID) {
            $the_post = get_post($the_entity->ID);
            foreach ($this->props as $prop) {
                $all_metas = get_post_meta($the_entity->ID);
                $prop->load($the_entity, $all_metas);
            }
            return $the_post;
        }
        return null;
    }
    public function persist_metas($the_entity)
    {
        foreach ($this->props as $prop) {
            $prop->persist($the_entity);
        }
    }
}
