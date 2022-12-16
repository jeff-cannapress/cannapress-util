<?php

declare(strict_types=1);

namespace CannaPress\Util;

use CannaPress\Util\DependsOn;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ReflectionClass;

class Container implements \Psr\Container\ContainerInterface
{
    protected array $providers;
    public function __construct(protected string $plugin_root_dir, private $prefix,  array $providers)
    {
        $providers = self::ensure_providers_is_map($providers);
        if (!isset($providers[Env::class])) {
            $providers[Env::class] = self::default_env($plugin_root_dir);
        }
        if (!isset($providers[\Psr\Log\LoggerInterface::class])) {
            $providers[\Psr\Log\LoggerInterface::class] = self::default_logger($plugin_root_dir, $plugin_root_dir);
        }


        $keys = array_keys($providers);
        foreach ($keys as $service) {
            if (is_string($providers[$service]) && class_exists($providers[$service])) {
                $providers[$service] = self::create_provider($providers[$service]);
            }
            if (!is_callable($providers[$service])) {
                $providers[$service] = self::singleton($providers[$service]);
            }
        }
        $this->providers = $providers;
        do_action($prefix . '_container_initialized', $this);
    }
    public static function ensure_providers_is_map($providers)
    {
        $result = [];
        foreach (array_keys($providers) as $key) {
            if (is_int($key)) {
                $result[$providers[$key]] = $providers[$key];
            } else {
                $result[$key] = $providers[$key];
            }
        }
        return $result;
    }
    public static function default_env(string $plugin_root_dir)
    {
        return Container::singleton(fn ($ctx) => Env::create(trailingslashit($plugin_root_dir) . '.env'));
    }
    public static function default_logger(string $plugin_root_dir, $prefix)
    {
        return self::singleton(function ($ctx) use ($plugin_root_dir, $prefix) {
            $level = Logger::toMonologLevel(strtolower(LogLevel::DEBUG));
            $path =  trailingslashit($plugin_root_dir) . 'logs/' . $prefix . '.log';
            $logger = new Logger($prefix);
            $handler = new StreamHandler($path, $level);
            $handler->setFormatter(new JsonFormatter());
            $logger->pushHandler($handler);
            return $logger;
        });
    }

    protected function register_container_hooks()
    {
        $actions = $this->get_reflected_hooks();
        $services = [];
        foreach ($actions as $action_name => $hooks) {
            foreach ($hooks as $hook) {
                if (!isset($services[$hook->service])) {
                    $t = $this->get($hook->service);
                    $services[$hook->service] = $t;
                }
                add_action($action_name, [$services[$hook->service], $hook->method], $hook->priority, $hook->accepted_args);
            }
        }
    }
    protected function get_reflected_hooks()
    {
        $results = [];
        foreach ($this->services() as $s) {
            if (class_exists($s)) {
                $key = $this->prefix . 'svc_hooks_' . \CannaPress\Util\Hashes::fast($s);
                $service_hooks = \CannaPress\Util\TransientCache::get_transient($key);
                if ($service_hooks === false) {
                    $service_hooks = [];
                    $clazz = new ReflectionClass($s);

                    foreach ($clazz->getAttributes(ActionHook::class) as $attr) {
                        /** @var ActionHook */ $inst = $attr->newInstance();
                        try {
                            $inst->method = $clazz->getMethod($inst->method_name);
                            if (!isset($service_hooks[$inst->hook_name])) {
                                $service_hooks[$inst->hook_name] = [];
                            }
                            $service_hooks[$inst->hook_name][] = (object)['service' => $s, 'method' => $inst->method->getName(), 'priority' => $inst->priority, 'accepted_args' => count($inst->method->getParameters())];
                        } catch (\ReflectionException) {
                            //snarf;
                        }
                    }
                    foreach ($clazz->getMethods() as $method) {
                        foreach ($method->getAttributes(ActionHook::class) as $attr) {
                            $inst = $attr->newInstance();
                            if (!isset($inst->hook_name)) {
                                $inst->hook_name = preg_replace('/^on_/', '', $method->name, 1);
                            }
                            if (!isset($service_hooks[$inst->hook_name])) {
                                $service_hooks[$inst->hook_name] = [];
                            }
                            $service_hooks[$inst->hook_name][] = (object)['service' => $s, 'method' => $method->getName(), 'priority' => $inst->priority, 'accepted_args' => count($method->getParameters())];
                        }
                    }
                    \CannaPress\Util\TransientCache::set_transient($key, $service_hooks);
                }
                foreach ($service_hooks as $hook_name => $metas) {
                    if (!isset($results[$hook_name])) {
                        $results[$hook_name] = [];
                    }
                    $results[$hook_name] = array_merge($results[$hook_name], $metas);
                }
            }
        }
        return $results;
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

    protected function create_provider(string $service_impl)
    {
        $key = $this->prefix . 'svc_args_' . \CannaPress\Util\Hashes::fast($service_impl);
        $arg_types = \CannaPress\Util\TransientCache::get_transient($key);
        if ($arg_types === false) {
            $arg_types = self::extract_service_constructor_args($service_impl);
            \CannaPress\Util\TransientCache::set_transient($key, $arg_types);
        }
        return self::singleton(function ($ctx) use ($service_impl, $arg_types, $key) {
            try {
                $args = [];
                foreach ($arg_types as $type) {
                    $args[] = $ctx->get($type);
                }
                $instance = new $service_impl(...$args);
                return $instance;
            } catch (\TypeError $err) {
                $arg_types = Container::extract_service_constructor_args($service_impl);
                \CannaPress\Util\TransientCache::set_transient($key, $arg_types);
                $args = [];
                foreach ($arg_types as $type) {
                    $args[] = $ctx->get($type);
                }
                $instance = new $service_impl(...$args);
                return $instance;
            }
        });
    }
    protected function services(): array
    {
        return array_keys($this->providers);
    }
    public function has($identifier): bool
    {
        return array_key_exists($identifier, $this->providers) && !is_null($this->providers[$identifier]);
    }
    protected function get_provider($id)
    {
        return $this->providers[$id];
    }
    public function get(string $id): mixed
    {
        $result = null;
        if ($this->has($id)) {
            $provider = $this->get_provider($id);
            $result = ($provider)($this);
        }
        $result = apply_filters($this->prefix . '_container_make', $result, $id, $this);
        return $result;
    }
    public function add(string $identifier, callable | object $providerOrInstance)
    {
        if (!empty($identifier) && !is_null($providerOrInstance)) {
            if (!is_callable($providerOrInstance)) {
                $providerOrInstance = self::singleton($providerOrInstance);
            }
            $this->providers[$identifier] = $providerOrInstance;
        }
        return $this;
    }
    public function remove($identifier): bool
    {
        if ($this->has($identifier)) {
            unset($this->providers[$identifier]);
            return true;
        }
        return false;
    }
    public static function singleton(callable|object $provider): callable
    {
        return new class($provider)
        {
            private $instance;
            public function __construct(private $provider)
            {
            }
            public function __invoke(Container $container)
            {
                if (!isset($this->instance)) {
                    if (is_callable($this->provider)) {
                        $this->instance = ($this->provider)($container);
                    } else {
                        $this->instance = $this->provider;
                    }
                }
                return $this->instance;
            }
        };
    }
}
