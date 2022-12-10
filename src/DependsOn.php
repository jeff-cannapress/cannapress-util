<?php

//declare(strict_types=1);

namespace CannaPress\Util;

use Attribute;
#[Attribute(Attribute::TARGET_PARAMETER)]
class DependsOn {

    public function __construct(public string $service_name)
    {

    }
}