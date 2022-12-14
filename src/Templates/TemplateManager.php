<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use CannaPress\Util\Container;
use Exception;

class TemplateManager
{
    public const filter_prefix = 'cannapress_template_manager';
    public function __construct(
        protected Container $container,
        protected PathResolver $path_resolver
    ) {
    }
    public static function name(...$parts)
    {
        return self::class . '/' . implode('/', $parts);
    }
    public static function singleton(string $which, $what = 'templates')
    {
        return Container::singleton(fn (Container $ctx) => new TemplateManager($ctx, $ctx->get(PathResolver::name($what))->child($which)));
    }

    protected function make_template_instance_factory($file_name, $container_identifier)
    {
        $factory = TemplateManagerHooks::before_make_template_instance_factory(null, $file_name, $container_identifier);
        if (is_null($factory)) {
            $factory = new TemplateInstanceFactory($file_name, $container_identifier);
            $factory = TemplateManagerHooks::make_template_instance_factory($factory, $container_identifier, $file_name);
        }
        return $factory;
    }
    protected function get_template_part_instance_factory($name)
    {
        $container_identifier = $this->path_resolver->get_template_identifier($name);
        if (!$this->container->has($container_identifier)) {
            $file_name = $this->path_resolver->get_absolute_filename($name);
            if (!empty($file_name)) {
                $this->container->add($container_identifier, Container::singleton($this->make_template_instance_factory($file_name, $container_identifier)));
            }
        }
        return $this->container->get($container_identifier);
    }
    public static function extract_parent_context($dbg)
    {
        $limit = min(12, count($dbg));
        for ($i = 2; $i < $limit; $i++) {
            if (isset($dbg[$i]['object'])) {
                $name = get_class($dbg[$i]['object']);
                $is_defined = defined("$name::CANNAPRESS_IS_TEMPLATE_INSTANCE");
                if ($is_defined) {
                    return get_object_vars($dbg[$i]['object']);
                }
            }
        }
        return [];
    }

    public function find_template_part($name, $instance_props = [])
    {
        $parent_props = self::extract_parent_context(debug_backtrace());
        $instance_props = array_merge($parent_props, $instance_props);
        $factory =  $this->get_template_part_instance_factory($name);
        if (!$factory) {
            throw new Exception("No template found for '$name'");
        }
        return $factory->create($instance_props);
    }
    /**
     * Renders a template out to the buffer
     */
    public function get_template_part($name, $instance_props = []): void
    {
        $instance = $this->find_template_part($name, $instance_props);
        $instance->emit();
    }
    /**
     * Renders a template out to the buffer
     */
    public function get_template_content($name, $instance_props = []): string
    {
        $instance = $this->find_template_part($name, $instance_props);
        return $instance->render();
    }
}
