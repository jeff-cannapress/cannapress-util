<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;


use Exception;

class TemplateNotFoundException extends Exception
{
    public function __construct(public string $name)
    {
        parent::__construct("No template found for '$name'");
    }
}
