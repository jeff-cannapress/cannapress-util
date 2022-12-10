<?php

declare(strict_types=1);

namespace CannaPress\Util\Proxies;

class Invocation
{
    public mixed $result;
    public function __construct(public object|null $proxied_object, public string $method, public array $args)
    {
    }
}
