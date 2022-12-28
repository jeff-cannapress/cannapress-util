<?php

declare(strict_types=1);

namespace CannaPress\Util;

use CannaPress\Util\DependsOn;

use ReflectionClass;

class AutoProvider
{
    private $instance = null;

    public function __construct(private string $service_impl)
    {
    }
    public function __invoke(Container $ctx)
    {
        if ($this->instance === null) {
            $key = 'svc_args_' . \CannaPress\Util\Hashes::fast(get_class($ctx) . '--' . $this->service_impl);
            $arg_types = \CannaPress\Util\TransientCache::get_transient($key);
            if ($arg_types === false) {
                $arg_types = self::extract_service_constructor_args($this->service_impl);
                \CannaPress\Util\TransientCache::set_transient($key, $arg_types);
            }
            try {
                $args = [];
                foreach ($arg_types as $type) {
                    $args[] = $ctx->get($type);
                }
                $this->instance = new ($this->service_impl)(...$args);
            } catch (\TypeError $err) {
                $arg_types = Container::extract_service_constructor_args($this->service_impl);
                \CannaPress\Util\TransientCache::set_transient($key, $arg_types);
                $args = [];
                foreach ($arg_types as $type) {
                    $args[] = $ctx->get($type);
                }
                $this->instance = new ($this->service_impl)(...$args);
            }
        }
        return $this->instance;
    }

    public static function extract_service_constructor_args($service_impl)
    {
        $arg_types = [];
        $clazz = new ReflectionClass($service_impl);
        $constructor = $clazz->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $p) {
                $attrs = $p->getAttributes(DependsOn::class);
                if (!empty($attrs)) {
                    $arg_types[] = $attrs[0]->newInstance()->service_name;
                } else {
                    $arg_types[] = $p->getType()->getName();
                }
            }
        }
        return $arg_types;
    }
}
