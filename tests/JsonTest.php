<?php

//declare(strict_types=1);

namespace CannaPress\Util\Tests;

use CannaPress\Util\Hashes;
use PHPUnit\Framework\TestCase;
use CannaPress\Util\Json\MetaInfo;
use CannaPress\Util\Proxies\Interceptor;
use CannaPress\Util\Proxies\Invocation;
use CannaPress\Util\Proxies\ProxyFactory;
use CannaPress\Util\UUID;
use DateTimeImmutable;
use JsonSerializable;
use Psr\Log\LoggerInterface;

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
    public function testCanGen(): void
    {
        $dir = '/tmp/cannapress-util-proxy/test_' . Hashes::uuid();

        $pg = new ProxyFactory($dir);
        $proxyInstance = $pg->create(ToProxy::class, new ToProxy("", 120, new ChildClass([
            'given_name' => 'Larry',
            'family_name' => "Parallelogram",
            'email' => 'larry.parallelogram@demo-cannabis-company.com',
            'company_name' => 'DEMO CANNABIS COMPANY'
        ])), new LoggingInterceptor());


        $proxyInstance->foo(12);
        $value = $proxyInstance->bar();
        $this->assertEquals('Hello, World', $value);
    }
    public function testCanGenInterface(): void
    {
        $dir = '/tmp/cannapress-util-proxy/test_' . Hashes::uuid();
        $pg = new ProxyFactory($dir);
        /**@var LoggerInterface */
        $proxyInstance = $pg->create(LoggerInterface::class, null, new class implements Interceptor
        {
            public function supports(Invocation $invocation): bool
            {
                return true;
            }
            public function invoke(Invocation $invocation): void
            {
                var_dump($invocation);
            }
        });
        $proxyInstance->log(123, "abcd", ['a' => 123]);
    }
    
}

class LoggingInterceptor implements Interceptor
{
    public function supports(Invocation $invocation): bool
    {
        return true;
    }
    public function invoke(Invocation $invocation): void
    {
        var_dump($invocation);
        if ($invocation->method === 'bar') {
            $invocation->result = 'Hello, World';
        }
    }
}
class ToProxy
{
    public function __construct(private string $a, public int $b, ChildClass $c)
    {
    }
    public function foo(int $a = 0): void
    {
    }
    public function bar(): string
    {
        return "";
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
