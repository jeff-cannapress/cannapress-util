<?php

declare(strict_types=1);

namespace CannaPress\Util;

use CannaPress\Util\DependsOn;
use DateTimeImmutable;
use DateTimeZone;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use ReflectionClass;
use SplFileObject;

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

    public static function create_default_logger($path): LoggerInterface
    {
        return new class($path) extends AbstractLogger
        {
            private ?\SplFileObject $file = null;
            public function __construct(private string $path)
            {
            }
            private static function ensure_dir(string $dir)
            {
                if (!file_exists($dir)) {
                    self::ensure_dir(dirname($dir));
                    mkdir($dir, 0777, true);
                }
            }

            private function get_file(): \SplFileObject
            {
                if ($this->file === null) {
                    self::ensure_dir(dirname($this->path));
                    $this->file = new \SplFileObject($this->path, 'a+');
                }
                return $this->file;
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $record = $this->build_record($level, $message, $context);
                $file = $this->get_file();
                $file->fwrite($record);
            }
            const DEFAULT_JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;
            private static function build_record($level, string|\Stringable $message, array $context = [])
            {
                $output = '['.strtoupper($level).'/'.(new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeImmutable::ISO8601).']:'.strval($message);                
                $ctx = array_merge($context);
                foreach ($ctx as $var => $val) {
                    if (false !== strpos($output, '%context.'.$var.'%')) {
                        $output = str_replace('%context.'.$var.'%', self::to_string($val), $output);
                        unset($ctx[$var]);
                    }
                }
                if(!empty($ctx)){
                    $output .= ' '. var_export($ctx, true);
                }
                return $output;
            }
            /**
             * @param mixed $data
             */
            protected static function to_string($data): string
            {
                if (null === $data || is_bool($data)) {
                    return var_export($data, true);
                }
                if (is_scalar($data)) {
                    return (string) $data;
                }
                if (is_object($data) && $data instanceof \Throwable) {
                    return json_encode(self::objectify_exception($data), self::DEFAULT_JSON_FLAGS);
                }
                return json_encode($data, self::DEFAULT_JSON_FLAGS);
            }


            private static function objectify_exception(\Throwable|null $e): object
            {
                if ($e !== null) {
                    return (object)[
                        'type' => get_class($e),
                        'code' => $e->getCode(),
                        'message' => $e->getMessage(),
                        'at' => $e->getFile() . ':' . $e->getLine(),
                        'trace' => $e->getTrace(),
                        'previous' => self::objectify_exception($e->getPrevious())
                    ];
                }
            }
        };
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
