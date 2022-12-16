<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use CannaPress\Util\Container;

class DirectoryResolver
{
    private array|null $resolved_paths = null;
    public function __construct(
        protected string $theme_override_dir,
        protected string $plugin_template_directory,
        protected string $relative_path = ''
    ) {
        $this->get_possible_template_directories();
    }
    public function child_resolver(string $path)
    {
        $path = empty($this->relative_path) ? $path : trailingslashit($this->relative_path) . $path;
        return new DirectoryResolver($this->theme_override_dir, $this->plugin_template_directory, untrailingslashit($path));
    }

    public static function name(string $name)
    {
        return self::class . '/' . $name;
    }
    public static function singleton(string $plugin_dir, string $theme_overrides_folder, string $which)
    {
        return Container::singleton(new DirectoryResolver(
            TemplateManagerHooks::get_theme_overrides_folder($theme_overrides_folder, $plugin_dir, $which),
            trailingslashit($plugin_dir . $which)
        ));
    }
    public function get_possible_template_directories()
    {
        if (is_null($this->resolved_paths)) {
            $resolved_paths = TemplateManagerHooks::before_get_possible_template_directories([]);
            if (empty($resolved_paths)) {
                $theme_directory = trailingslashit($this->theme_override_dir);

                $resolved_paths = array(
                    10  => trailingslashit(get_template_directory()) . $theme_directory,
                    15  => get_template_directory(),
                    100000 => $this->plugin_template_directory,
                );

                // Only add this conditionally, so non-child themes don't redundantly check active theme twice.
                if (get_stylesheet_directory() !== get_template_directory()) {
                    $resolved_paths[1] = trailingslashit(get_stylesheet_directory()) . $theme_directory;
                }
                if (!empty($this->relative_path)) {
                    foreach (array_keys($resolved_paths) as $key) {
                        $resolved_paths[$key] = trailingslashit($resolved_paths[$key]) . $this->relative_path;
                    }
                }
                ksort($resolved_paths, SORT_NUMERIC);
                $resolved_paths = TemplateManagerHooks::get_possible_template_directories($resolved_paths);
            }
            // Sort the file paths based on priority.            
            $this->resolved_paths = array_map('trailingslashit', $resolved_paths);
        }
        return $this->resolved_paths;
    }
    public function get_template_identifier(string $name): string
    {
        return $this->plugin_template_directory . '/' . ltrim($name, '/\\');
    }
}
