<?php

declare(strict_types=1);

namespace CannaPress\Util;


trait HooksSurface
{
    protected static abstract function prefix(): array;
    protected static function name(...$parts)
    {
        $parts = [...self::prefix(), ...$parts];
        return implode('_', $parts);
    }
    protected static function apply_filters($caller, ...$args): mixed
    {
        return apply_filters(self::name($caller), ...$args);
    }
    protected static function do_action($caller, ...$args):void
    {
        do_action(self::name($caller, ...$args));
    }
}
