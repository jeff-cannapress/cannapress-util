<?php

declare(strict_types=1);

namespace CannaPress\Util;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Container implements \Psr\Container\ContainerInterface
{
    public function __construct($plugin_root_dir, private $prefix, private array $providers)
    {
        if(!isset($providers[Env::class])){
            $this->providers[Env::class] = Container::singleton(fn($ctx)=> Env::create(trailingslashit($plugin_root_dir).'.env'));
        }
        if(!isset($providers[\Psr\Log\LoggerInterface::class])){
            $this->providers[\Psr\Log\LoggerInterface::class] = Container::singleton(function($ctx) use ($plugin_root_dir, $prefix){
                $env = $ctx->get(Env::class)->create_child('CANNAPRESS');

                if($env->ENVIRONMENT === 'DEVELOPMENT'){
                    $level = Logger::toMonologLevel(strtolower($env->LOG_LEVEL ?? LogLevel::DEBUG));
                    $path = $env->LOG_PATH  ?? trailingslashit($plugin_root_dir).'logs/retail.log';
                    $logger = new Logger($prefix);
                    $handler = new StreamHandler($path, $level);
                    $handler->setFormatter(new JsonFormatter());
                    $logger->pushHandler($handler);
                    return $logger;
                }
                return new NullLogger();
            });
        }
        do_action($prefix . '_container_initialized', $this);
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
