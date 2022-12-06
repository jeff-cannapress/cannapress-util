<?php

declare(strict_types=1);

namespace CannaPress\Util;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class ActionHook
{
    public ?\ReflectionMethod $method;
    public function __construct(public ?string $hook_name = null, public $method_name = null, public bool $require_admin = false, public int $priority = 10)
    {
    }
}
