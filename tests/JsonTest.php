<?php
declare(strict_types=1);
namespace CannaPress\Util\Tests;



use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use JsonSerializable;
use CannaPress\Util\Json\MetaInfo;

final class JsonTest extends TestCase
{
    public function testCanBeCreatedFromValidEmailAddress(): void
    {
        $result = ParentClass::jsonDeserialize([
            //'license_key' => 'expected_key',
            'licensed_to' => [
                'given_name' => 'Larry',
                'family_name' => "Parallelogram",
                'email' => 'larry.parallelogram@demo-cannabis-company.com',
                'company_name' => 'DEMO CANNABIS COMPANY'
            ]
        ]);
        $this->assertNotNull($result->licensed_to);
    }
}


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
