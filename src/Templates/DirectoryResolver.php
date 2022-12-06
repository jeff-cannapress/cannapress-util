<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class DirectoryResolver
{
    private array|null $resolved_paths = null;
    public function __construct(
        protected string $theme_template_directory,
        protected string $plugin_template_directory,
        protected string $relative_path = ''
    ) {
        $this->get_possible_template_folders();
    }
    public function child_resolver(string $path)
    {
        $path = empty($this->relative_path) ? $path : trailingslashit($this->relative_path) . $path;
        return new DirectoryResolver($this->theme_template_directory, $this->plugin_template_directory, untrailingslashit($path));
    }
    protected function apply_filter($name, $item, ...$rest)
    {
        return TemplateManager::apply_filters($name, ...[$item, ...$rest]);
    }
    public static function name(string $name)
    {
        return self::class . '/' . $name;
    }

    public function get_possible_template_folders()
    {
        if (is_null($this->resolved_paths)) {
            $theme_directory = trailingslashit($this->theme_template_directory);

            $file_paths = array(
                10  => trailingslashit(get_template_directory()) . $theme_directory,
                15  => get_template_directory(),
                100000 => $this->plugin_template_directory,
            );

            // Only add this conditionally, so non-child themes don't redundantly check active theme twice.
            if (get_stylesheet_directory() !== get_template_directory()) {
                $file_paths[1] = trailingslashit(get_stylesheet_directory()) . $theme_directory;
            }
            if (!empty($this->relative_path)) {
                foreach (array_keys($file_paths) as $key) {
                    $file_paths[$key] = trailingslashit($file_paths[$key]) . $this->relative_path;
                }
            }
            // Sort the file paths based on priority.
            ksort($file_paths, SORT_NUMERIC);
            $file_paths = $this->apply_filter(__FUNCTION__, $file_paths);
            $this->resolved_paths = array_map('trailingslashit', $file_paths);
        }
        return $this->resolved_paths;
    }
    public function get_template_identifier(string $name): string
    {
        return $this->plugin_template_directory . '/' . ltrim($name, '/\\');
    }
}
