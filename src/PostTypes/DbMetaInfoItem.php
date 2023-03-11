<?php

declare(strict_types=1);

namespace CannaPress\Util\PostTypes;

use DateTimeImmutable;
use DateTimeZone;

interface DbMetaInfoItem
{
    public function load(object $the_entity, array $all_metas);
    public function persist($entity_id, $the_entity);
}