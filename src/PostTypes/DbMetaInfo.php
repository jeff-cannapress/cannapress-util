<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

use WP_Post;

abstract class DbMetaInfo
{
    private array $props;
    public function __construct(array $props)
    {
        $this->props = $props;
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

    public function fill($the_entity, WP_Post $the_post = null)
    {
        if ($the_entity->ID) {
            $the_post = get_post($the_entity->ID);
            $all_metas = get_post_meta($the_entity->ID);
            foreach ($this->props as $prop) {
                $prop->load($the_entity, $all_metas);
            }
            $this->after_props_loaded($the_entity, $the_post);
            return $the_post;
        }
        return null;
    }
    public function persist_metas($id, $the_entity)
    {
        $this->before_persist_metas($the_entity);
        foreach ($this->props as $prop) {
            $prop->persist($id, $the_entity);
        }
    }
    protected function after_props_loaded($the_entity, \WP_Post|null $the_post)
    {
    }

    public abstract function get_implementation_type(): string;
    public abstract function get_insert_props(object $instance): array;
    public function after_persist_result($result)
    {
        return $result;
    }
    public function before_persist_metas($instance)
    {
    }
}
