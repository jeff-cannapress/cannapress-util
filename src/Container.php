<?php

declare(strict_types=1);

namespace CannaPress\Util;

use CannaPress\Util\DependsOn;
use CannaPress\Util\Templates\PathResolver;
use CannaPress\Util\Templates\TemplateManager;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ReflectionClass;

class Container implements \Psr\Container\ContainerInterface
{
    public function __construct(private string $plugin_root_dir, private $prefix, private array $providers)
    {
        if (!isset($this->providers[Env::class])) {
            $this->providers[Env::class] = Container::singleton(fn ($ctx) => Env::create(trailingslashit($plugin_root_dir) . '.env'));
        }
        if (!isset($this->providers[\Psr\Log\LoggerInterface::class])) {
            $this->providers[\Psr\Log\LoggerInterface::class] = Container::singleton(function ($ctx) use ($plugin_root_dir, $prefix) {
                $env = $ctx->get(Env::class)->create_child('CANNAPRESS');

                if ($env->ENVIRONMENT === 'DEVELOPMENT') {
                    $level = Logger::toMonologLevel(strtolower($env->LOG_LEVEL ?? LogLevel::DEBUG));
                    $path = $env->LOG_PATH  ?? trailingslashit($plugin_root_dir) . 'logs/' . $prefix . '.log';
                    $logger = new Logger($prefix);
                    $handler = new StreamHandler($path, $level);
                    $handler->setFormatter(new JsonFormatter());
                    $logger->pushHandler($handler);
                    return $logger;
                }
                return new NullLogger();
            });
        }

        $numeric =  array_values(array_filter(array_keys($this->providers), fn ($k) => is_int($k)));
        foreach ($numeric as $n) {
            $key = $this->providers[$n];
            unset($this->providers[$n]);
            $this->providers[$key] = $key;
        }
        $keys = array_keys($this->providers);
        foreach ($keys as $service) {
            if (is_string($this->providers[$service]) && class_exists($this->providers[$service])) {
                $this->providers[$service] = self::create_provider($this->providers[$service]);
            }
            if (!is_callable($this->providers[$service])) {
                $this->providers[$service] = self::singleton($this->providers[$service]);
            }
        }
        do_action($prefix . '_container_initialized', $this);
    }
    protected function create_template_manager(string $which, string $text_domain)
    {
        $dir = $this->plugin_root_dir;
        return Container::singleton(fn ($ctx) => new TemplateManager(
            $ctx,
            new PathResolver(
                $text_domain,
                TemplateManager::apply_filters('get_theme_overrides_folder', ($text_domain. '/templates/' . $which), $which),
                $dir . 'templates/' . $which
            )
        ));
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
                $clazz = new ReflectionClass($s);

                foreach ($clazz->getAttributes(ActionHook::class) as $attr) {
                    /** @var ActionHook */ $inst = $attr->newInstance();
                    try {
                        $inst->method = $clazz->getMethod($inst->method_name);
                        if (!isset($results[$inst->hook_name])) {
                            $results[$inst->hook_name] = [];
                        }
                        $results[$inst->hook_name][] = (object)['service' => $s, 'method' => $inst->method->getName(), 'priority' => $inst->priority, 'accepted_args' => count($inst->method->getParameters())];
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
                        if (!isset($results[$inst->hook_name])) {
                            $results[$inst->hook_name] = [];
                        }
                        $results[$inst->hook_name][] = (object)['service' => $s, 'method' => $method->getName(), 'priority' => $inst->priority, 'accepted_args' => count($method->getParameters())];
                    }
                }
            }
        }
        return $results;
    }

    protected function create_provider(string $service_impl)
    {
        $clazz = new ReflectionClass($service_impl);
        $constructor = $clazz->getConstructor();
        if ($constructor === null) {
            return self::singleton(fn ($ctx) => new $service_impl());
        }
        $argTypes = [];
        foreach ($constructor->getParameters() as $p) {
            $attrs = $p->getAttributes(DependsOn::class);
            if (!empty($attrs)) {
                $argTypes[] = $attrs[0]->newInstance()->service_name;
            } else {
                $argTypes[] = $p->getType()->getName();
            }
        }

        return self::singleton(function ($ctx) use ($clazz, $argTypes) {
            $args = [];
            foreach ($argTypes as $type) {
                $args[] = $ctx->get($type);
            }
            return $clazz->newInstanceArgs($args);
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
    public function get(string $id): mixed
    {
        $result = null;
        if ($this->has($id)) {
            $result = ($this->providers[$id])($this);
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
