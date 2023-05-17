<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

class PropMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private $prop, private $meta_key, private $default, private $on_save = null, private $on_load = null)
    {
    }
    public function coerce_loaded($value)
    {
        if (!is_null($this->default)) {
            if (is_int($this->default)) {
                $value = intval($value);
            } elseif (is_float($this->default)) {
                $value = floatval($value);
            } elseif (is_bool($this->default)) {
                $value = boolval($value);
            }
        }
        if (!is_null($this->on_load)) {
            $value = ($this->on_load)($value);
        }
        return $value;
    }
    public function load(object $the_entity, array $all_metas)
    {
        $multiple = is_array($this->default);
        $strval = DbMetaInfo::get_meta_value($all_metas, $this->meta_key, $this->default, $multiple);
        $the_entity->{$this->prop} = $this->coerce_loaded($strval);
    }
    public function persist($entity_id, $the_entity)
    {
        $multiple = is_array($this->default);
        if (property_exists($the_entity, $this->prop)) {
            $to_save = $this->coerce_saving($the_entity->{($this->prop)});
            if ($multiple) {
                delete_post_meta($entity_id, $this->meta_key);
                foreach ($to_save as $value) {
                    add_post_meta($entity_id, $this->meta_key, $value);
                }
            } else {
                update_post_meta($entity_id, $this->meta_key, $to_save);
            }
        }
    }
    public function coerce_saving($value)
    {
        if (!is_null($this->on_save)) {
            $value = ($this->on_save)($value);
        }
        return $value;
    }
}