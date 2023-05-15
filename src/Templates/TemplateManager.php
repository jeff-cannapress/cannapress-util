<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use CannaPress\Util\Container;

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


    protected function make_template_instance_factory($file_name, $container_identifier)
    {
        return new TemplateInstanceFactory($file_name, $container_identifier);;
    }
    protected function get_template_part_instance_factory($name)
    {
        $container_identifier = $this->path_resolver->get_template_identifier($name);
        if (!$this->container->has($container_identifier)) {
            $file_name = $this->path_resolver->get_absolute_filename($name);
            if (!empty($file_name)) {
                $this->container->add($container_identifier, Container::singleton($this->make_template_instance_factory($file_name, $container_identifier), $container_identifier));
            }
        }
        return $this->container->get($container_identifier);
    }
    public function find_template_part($name, $instance_props = [])
    {
        $factory =  $this->get_template_part_instance_factory($name);
        if (!$factory) {
            throw new TemplateNotFoundException("No template found for '$name'");
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
