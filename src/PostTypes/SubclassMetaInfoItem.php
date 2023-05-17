<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

class SubclassMetaInfoItem implements DbMetaInfoItem
{
    public function __construct(private string $prop, private $class_name, private array $metas)
    {
    }
    public function persist($entity_id, $the_entity)
    {
        $prop = $the_entity->{$this->prop};
        foreach ($this->metas as $meta) {
            $meta->persist($entity_id, $prop);
        }
    }
    public function load(object $the_entity, array $all_metas)
    {
        $value = new $this->class_name();
        foreach ($this->metas as $meta) {
            $meta->load($value, $all_metas);
        }
        $the_entity->{$this->prop} = $value;
    }
}
