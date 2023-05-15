<?php

// declare(strict_types=1);

// namespace CannaPress\Util;

// use CannaPress\Util\DependsOn;

// use ReflectionClass;

// class AutoProvider implements ContainerSingleton
// {
//     private $instance = null;
//     private array|false $arg_types = false;

//     public function __construct(private Container $container, private string $service_impl, private string $key)
//     {
//     }

//     public function get_key(): string
//     {
//         return $this->key;
//     }
//     private function get_arg_types($force = false)
//     {
//         $key = 'svc_args_' . \CannaPress\Util\Hashes::fast(get_class($this->container) . '--' . $this->service_impl);
//         $this->arg_types = \CannaPress\Util\TransientCache::get_transient($key);
//         if ($this->arg_types === false || $force) {
//             $this->arg_types = $this->extract_service_constructor_args($this->service_impl);
//             \CannaPress\Util\TransientCache::set_transient($key, $this->arg_types);
//         }
//         return $this->arg_types;
//     }
//     public function __invoke(Container $ctx):mixed
//     {
//         if ($this->instance === null) {

//             try {
//                 $this->instance = $this->get_instance($ctx, false);
//             } catch (\TypeError $err) {
//                 $this->instance = $this->get_instance($ctx, true);
//             }
//         }
//         return $this->instance;
//     }
//     private function get_instance(Container $ctx, $force = false)
//     {
//         $args = [];
//         $arg_types = $this->get_arg_types($force);
//         foreach ($arg_types as $type) {
//             $args[] = $ctx->get($type);
//         }
//         $result = new ($this->service_impl)(...$args);
//         $ctx->add_static_load($this->key, $this->service_impl, $arg_types);
//         return $result;
//     }

//     private function extract_service_constructor_args($service_impl)
//     {

//         $arg_types = [];
//         $clazz = new ReflectionClass($service_impl);
//         $constructor = $clazz->getConstructor();
//         if ($constructor !== null) {
//             foreach ($constructor->getParameters() as $p) {
//                 $attrs = $p->getAttributes(DependsOn::class);
//                 if (!empty($attrs)) {
//                     $arg_types[] = $attrs[0]->newInstance()->service_name;
//                 } else {
//                     $arg_types[] = $p->getType()->getName();
//                 }
//             }
//         }
//         return $arg_types;
//     }
// }
