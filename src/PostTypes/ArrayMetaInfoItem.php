<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

class ArrayMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private $prop, private $meta_key, private bool $associative = false, private $on_save = null, private $on_load = null)
    {
    }
    public function coerce_loaded($value)
    {
        if (!is_null($this->on_load)) {
            $value = ($this->on_load)($value);
        }
        return $value;
    }
    public function load(object $the_entity, array $all_metas)
    {
        $items = DbMetaInfo::get_meta_value($all_metas, $this->meta_key, [], true);
        $the_entity->{$this->prop} = [];
        if ($this->associative) {
            $items = array_map(fn ($x) => explode(':', $x, 2), $items);
            foreach ($items as $item) {
                $the_entity->{$this->prop}[$item[0]] = $this->coerce_loaded($item[1]);
            }
        } else {
            foreach ($items as $item) {
                ($the_entity->{$this->prop})[] = $this->coerce_loaded($item);
            }
        }
    }
    public function persist($entity_id, $the_entity)
    {
        if (property_exists($the_entity, $this->prop)) {
            delete_post_meta($entity_id, $this->meta_key);
            if (!empty($the_entity->{($this->prop)})) {
                if ($this->associative) {
                    foreach ($the_entity->{($this->prop)} as $key => $value) {
                        $to_save = $this->coerce_saving($value);
                        $to_save = implode(':', [$key, strval($to_save)]);
                        add_post_meta($entity_id, $this->meta_key, $to_save);
                    }
                } else {
                    foreach ($the_entity->{($this->prop)} as $value) {
                        $to_save = $this->coerce_saving($value);
                        add_post_meta($entity_id, $this->meta_key, $to_save);
                    }
                }
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
