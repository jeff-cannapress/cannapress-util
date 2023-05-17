<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

use DateTimeZone;

class DateTimeZonePropMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private string $prop, private string $meta_key)
    {
    }

    public function load(object $the_entity, array $all_metas)
    {
        $data = DbMetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);
        if (!empty($data)) {
            $the_entity->{$this->prop} = new DateTimeZone($data);
        }
    }
    public function persist($entity_id, $the_entity)
    {
        if (!is_null($the_entity->{($this->prop)})) {
            $val = is_string($the_entity->{($this->prop)}) ? $the_entity->{($this->prop)} : $the_entity->{($this->prop)}->getName();
            update_post_meta($entity_id, $this->meta_key, $val);
        } else {
            delete_post_meta($entity_id, $this->meta_key);
        }
    }
}