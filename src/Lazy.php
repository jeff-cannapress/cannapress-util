<?php

//declare(strict_types=1);

namespace CannaPress\Util;

class Lazy
{
    private $have_constructed = false;
    private $inner_value = null;
    public function __construct(private $factory)
    {
    }
    public function __get($name)
    {
        if ('value' === $name) {
            if (!$this->have_constructed) {
                $this->inner_value = ($this->factory)();
                $this->have_constructed = true;
            }
            return $this->inner_value;
        }
    }
}
