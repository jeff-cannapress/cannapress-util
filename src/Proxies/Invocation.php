<?php

//declare(strict_types=1);

namespace CannaPress\Util\Proxies;

class Invocation
{
    public mixed $result;
    public function __construct(public object|null $proxied_object, public string $method, public array $args)
    {
    }
    public function proceed()
    {
        if ($this->proxied_object != null) {
            $this->result =  $this->proxied_object->{$this->method}(...$this->args);
        }
    }
}
