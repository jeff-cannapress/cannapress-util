<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

trait Dbable
{
    protected static $db_metas = null;
    protected abstract static function db_metas();

    public function save()
    {
        return self::persist($this);
    }

    protected function trait_constructor($the_post = null)
    {
        if (self::$db_metas === null) {
            self::$db_metas = self::db_metas();
        }
        $this->ID = (is_int($the_post) ? $the_post : (!is_null($the_post) ? $the_post->ID : 0));
        self::$db_metas->fill($this);
        return $the_post;
    }

    public static function persist($instance)
    {
        if (!is_object($instance)) {
            $instance = (object)$instance;
        }
        if (!isset($instance->ID)) {
            $instance->ID = null;
        }
        if (self::$db_metas === null) {
            self::$db_metas = self::db_metas();
        }
        $insert_props = self::$db_metas->get_insert_props($instance);
        $insert_props['ID'] = $instance->ID ?? 0;
        $id = wp_insert_post($insert_props);
        if (empty($instance->ID)) {
            $instance->ID = $id;
        }

        self::$db_metas->persist_metas($instance->ID, $instance);
        $implementation_type = self::$db_metas->get_implementation_type();
        $result = $instance;
        if (!is_a($instance, $implementation_type)) {
            $result = new ($implementation_type)($instance->ID);
        }
        $result = self::$db_metas->after_persist_result($result);
        return $result;
    }
}
