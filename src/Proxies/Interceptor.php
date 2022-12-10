<?php

//declare(strict_types=1);

namespace CannaPress\Util\Proxies;

interface Interceptor
{
    public function supports (Invocation $invocation):bool;
    public function invoke(Invocation $invocation):void;
}
