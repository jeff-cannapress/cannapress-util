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
}
