<?php
declare(strict_types=1);
namespace CannaPress\Util\Tests;


use JsonSerializable;
use CannaPress\Util\Json\MetaInfo;

#[\AllowDynamicProperties]
class ParentClass implements JsonSerializable
{
    use \CannaPress\Util\Json\Jsonable;
    public string $license_key = '';
    public ?ChildClass $licensed_to = null;



    public static function json_metas(): iterable
    {
        return [
            MetaInfo::prop('license_key', ''),
            MetaInfo::instance('licensed_to', ChildClass::class),
        ];
    }

    public static function unlicensed()
    {
        return new self();
    }
}
