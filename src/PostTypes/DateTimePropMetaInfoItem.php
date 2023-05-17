<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

use DateTimeImmutable;
use DateTimeZone;

class DateTimePropMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private string $prop, private string $meta_key)
    {
    }

    public function load(object $the_entity, array $all_metas)
    {
        $data = DbMetaInfo::get_meta_value($all_metas, $this->meta_key, null, false);
        $tzval = DbMetaInfo::get_meta_value($all_metas, $this->meta_key . '_tz', null, false);
        if (!empty($data)) {
            $tzval = $tzval ?? 'UTC';
            $the_entity->{$this->prop} = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $data)->setTimezone(new DateTimeZone($tzval));
        }
    }
    public function persist($entity_id, $the_entity)
    {
        if (!is_null($the_entity->{($this->prop)})) {
            update_post_meta($entity_id, $this->meta_key, $the_entity->{($this->prop)}->setTimezone(new DateTimeZone('UTC'))->format(DateTimeImmutable::ATOM));
            update_post_meta($entity_id, $this->meta_key . '_tz', $the_entity->{($this->prop)}->getTimezone()->getName());
        } else {
            delete_post_meta($entity_id, $this->meta_key);
            delete_post_meta($entity_id, $this->meta_key . '_tz');
        }
    }
}