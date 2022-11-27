<?php
declare(strict_types=1);
namespace CannaPress\Util\Tests;



use JsonSerializable;
use CannaPress\Util\Json\MetaInfo;

#[\AllowDynamicProperties]
class ChildClass implements JsonSerializable
{
    use \CannaPress\Util\Json\Jsonable;
    public string $given_name = '';
    public string $family_name = '';
    public string $email = '';
    public string $company_name = '';
    public static function json_metas(): iterable
    {
        return [
            MetaInfo::prop('given_name', ''),
            MetaInfo::prop('family_name', ''),
            MetaInfo::prop('email', ''),
            MetaInfo::prop('company_name', ''),
        ];
    }
}
