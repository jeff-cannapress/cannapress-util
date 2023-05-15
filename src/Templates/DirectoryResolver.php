<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use CannaPress\Util\Container;

class DirectoryResolver
{
    protected array|null $resolved_paths = null;
    public function __construct(
        protected string $theme_override_dir,
        protected string $plugin_template_directory,
        protected string $relative_path ='' 
    ) {
        $this->get_possible_template_directories();
    }
    public function child_resolver(string $path)
    {
        $path = empty($this->relative_path) ? $path : trailingslashit($this->relative_path) . $path;
        return new self($this->theme_override_dir, $this->plugin_template_directory, untrailingslashit($path));
    }

    public static function name(string $name)
    {
        return self::class . '/' . $name;
    }

    public static function private(string $abs_path): DirectoryResolver
    {
        return new class($abs_path) extends DirectoryResolver
        {
            public function __construct(string $plugin_template_directory)
            {
                parent::__construct('NOT_A_VALID_PATH', $plugin_template_directory);
            }
            public function get_possible_template_directories(): array
            {
                return [trailingslashit($this->plugin_template_directory)];
            }
        };
    }
    public function get_possible_template_directories(): array
    {
        if (is_null($this->resolved_paths)) {
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

            ksort($resolved_paths, SORT_NUMERIC);
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
