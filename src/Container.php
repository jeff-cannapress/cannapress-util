<?php

declare(strict_types=1);

namespace CannaPress\Util;

use CannaPress\Util\Logging\Logger;
use Psr\Log\NullLogger;
use ReflectionClass;

class Container implements \Psr\Container\ContainerInterface
{
    protected array $providers;
    public function __construct(protected string $plugin_root_dir, private $name, array $providers)
    {
        $this->providers = self::ensure_providers_is_map($providers);

        /** @var Env */
        $env  = null;
        if (!isset($this->providers[Env::class])) {
            $env = Env::create(trailingslashit($plugin_root_dir) . '.env');
            $this->providers[Env::class] = self::singleton($env);
        } else {
            $env = ($this->providers[Env::class])($this);
        }

        if (!isset($this->providers[\Psr\Log\LoggerInterface::class])) {
            $logger = $env->get('CANNAPRESS:' . strtoupper($this->name) . ':ENABLE_LOGGING', '0') === '1'
                ? Logger::default(trailingslashit($plugin_root_dir) . 'logs/' . $this->name . '.log')
                : new NullLogger();
            $this->providers[\Psr\Log\LoggerInterface::class] = self::singleton($logger);
        }
        do_action($this->name . '_container_initialized', $this);
    }
    public static function ensure_providers_is_map($providers, $create_provider = null)
    {
        if ($create_provider === null) {
            $create_provider = fn ($impl) => new AutoProvider($this, $impl);
        }
        $result = [];
        foreach (array_keys($providers) as $key) {
            $value = $providers[$key];
            if (is_int($key)) {
                if (is_string($value) && class_exists($value)) {
                    $result[$value] = $create_provider($value);
                } else if (is_object($value)) {
                    $result[get_class($value)] = self::singleton($value);
                } else {
                    $result[strval($key)] = self::singleton($value);
                }
            } else {
                if (is_string($value) && class_exists($value)) {
                    $result[$key] = $create_provider($value);
                } else if (!is_callable($value)) {
                    $result[$key] = self::singleton($value);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
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
                $key = $this->name . 'svc_hooks_' . \CannaPress\Util\Hashes::fast($s);
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

    protected function services(): array
    {
        return array_keys($this->providers);
    }
    public function has($identifier): bool
    {
        return array_key_exists($identifier, $this->providers) && !is_null($this->providers[$identifier]);
    }

    public function get_name()
    {
        return $this->name;
    }
    public function get_plugin_dir()
    {
        return rtrim($this->plugin_root_dir, '/');
    }

    public function get(string $id): mixed
    {
        $result = null;
        if ($this->has($id)) {
            $provider =  $this->providers[$id];
            $result = is_callable($provider) ? ($provider)($this) : $provider;
        }
        $result = apply_filters($this->name . '_container_create_instance', $result, $id, $this);
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
        if (!is_callable($provider)) {
            return fn ($ctx) => $provider;
        }
        return new class($provider)
        {
            private $instance;
            public function __construct(private $provider)
            {
            }
            public function __invoke(Container $container)
            {
                if (!isset($this->instance)) {
                    $this->instance = ($this->provider)($container);
                }
                return $this->instance;
            }
        };
    }
}
