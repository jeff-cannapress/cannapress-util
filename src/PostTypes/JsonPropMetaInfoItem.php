<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

class JsonPropMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private string $prop, private string $meta_key, private bool $associatve = false)
    {
    }

    public function load(object $the_entity, array $all_metas)
    {
        $strval = DbMetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);

        if (!empty($strval)) {
            $the_entity->{$this->prop} = json_decode($strval, $this->associatve);
        }
    }
    public function persist($entity_id, $the_entity)
    {
        update_post_meta($entity_id, $this->meta_key, json_encode($the_entity->{($this->prop)}));
    }
}
