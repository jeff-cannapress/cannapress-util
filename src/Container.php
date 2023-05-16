<?php

declare(strict_types=1);

namespace CannaPress\Util;


use Exception;
use Psr\Log\NullLogger;


class Container implements \Psr\Container\ContainerInterface
{
    protected array $providers;

    public function __construct(public string $plugin_root_dir, private $name, array|callable $providers)
    {
        $this->providers = $this->ensure_providers_is_map($providers);

        /** @var Env */
        $env  = null;
        if (!isset($this->providers[Env::class])) {
            $this->providers[Env::class] = $this->default_create_environment();
        }

        if (!isset($this->providers[\Psr\Log\LoggerInterface::class])) {
            $this->providers[\Psr\Log\LoggerInterface::class] = $this->default_create_logger();
        }
        if (is_callable($this->providers[\Psr\Log\LoggerInterface::class])) {
            $logger_instance = $this->providers[\Psr\Log\LoggerInterface::class]($this);
            $this->providers[\Psr\Log\LoggerInterface::class] = $logger_instance;
        }
        do_action($this->name . '_container_initialized', $this);
    }
    protected function default_create_environment(): Env
    {
        $env = Env::create(trailingslashit($this->plugin_root_dir) . '.env');
        return $env;
    }
    protected function default_create_logger()
    {
        return new NullLogger();
    }
    private function ensure_providers_is_map($providers)
    {
        $int_keys = [];
        $result = [];
        foreach (array_keys($providers) as $key) {
            if (is_int($key)) {
                $int_keys[$key] = $providers[$key];
            } else if (!is_callable($providers[$key])) {
                $result[$key] = self::singleton($providers[$key]);
            } else {
                $result[$key] = $providers[$key];
            }
        }
        if (!empty($int_keys)) {
            throw new class($int_keys) extends Exception implements \Psr\Container\ContainerExceptionInterface
            {
                public function __construct($bad_defs)
                {
                    parent::__construct("Error initializing container: numeric key(s) provided:\n" .  var_export($bad_defs, true));
                }
            };
        }
        return $providers;
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
        try {
            if ($this->has($id)) {
                $provider =  $this->providers[$id];
                $result = is_callable($provider) ? ($provider)($this) : $provider;
            }
            $result = apply_filters($this->name . '_container_create_instance', $result, $id, $this);
        } catch (\Throwable $ex) {
            $i = 0;
            $i++;
            $logger = $this->providers[\Psr\Log\LoggerInterface::class];
            $error_ctx =  [
                'message' => $ex->getMessage(),
                'stackTrace' => $ex->getTraceAsString()
            ];
            $logger->emergency('invalid provider configuration for ' . $id,$error_ctx);
            $i = 0;
            $i++;
        }
        return $result;
    }
    public function add(string $identifier, callable | object $providerOrInstance)
    {
        if (!empty($identifier) && !is_null($providerOrInstance)) {
            if (!is_callable($providerOrInstance)) {
                $providerOrInstance = self::singleton($providerOrInstance, $identifier);
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

            public function __invoke(Container $container): mixed
            {
                if (!isset($this->instance)) {
                    $this->instance = ($this->provider)($container);
                }
                return $this->instance;
            }
        };
    }
}
