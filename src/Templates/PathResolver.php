<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class PathResolver
{
    private array $template_path_cache = [];

    public function __construct(
        protected string $theme_template_directory,
        protected string $plugin_directory,
        protected string $plugin_template_directory
    ) {
    }
    protected function apply_filter($name, $item, ...$rest)
    {
        return TemplateManager::apply_filters($name, ...[$item, ...$rest]);
    }
    protected function get_file_names($name, $prefer = 'php')
    {
        $name = untrailingslashit($name);
        $templates = [];
        if ($prefer === 'php') {
            $templates[] = $name . '.php';
            $templates[] = $name . '.html';
            $templates[] = trailingslashit($name) . 'index.php';
            $templates[] = trailingslashit($name) . 'index.html';
        } else {
            $templates[] = $name . '.html';
            $templates[] = $name . '.php';
            $templates[] = trailingslashit($name) . 'index.html';
            $templates[] = trailingslashit($name) . 'index.php';
        }
        return $this->apply_filter(__FUNCTION__, $templates, $name);
    }
    public function get_template_absolute_filename(string $name, $prefer = 'php'): string
    {
        $file_name = $this->apply_filter('before_' . __FUNCTION__, null, $name);
        if (empty($file_name)) {
            $templates = $this->get_file_names($name, $prefer);
            $file_name = $this->select_file($templates, $name);
            $file_name = $this->apply_filter(__FUNCTION__, $file_name, $name);
        }
        if (false === $file_name) {
            return "";
        }
        return $file_name;
    }
    public function get_template_identifier(string $name): string
    {
        return $this->plugin_template_directory . '/' . ltrim($name, '/\\');
    }

    protected function select_file($template_names, $name)
    {
        $cache_key = is_array($template_names) ? $template_names[0] : $template_names;

        if (isset($this->template_path_cache[$cache_key])) {
            $located = $this->template_path_cache[$cache_key];
        } else {
            // No file found yet.
            $located = false;

            $possible_paths = $this->enumerate_possible_files($template_names, $cache_key, $name);
            foreach ($possible_paths as $file_name) {
                if (file_exists($file_name)) {
                    $this->template_path_cache[$cache_key] = $file_name;
                    $located = $file_name;
                    break;
                }
            }
        }
        $located = $this->apply_filter(__FUNCTION__, $located, $name);
        $this->template_path_cache[$cache_key] = $located;
        return $located;
    }

    protected function enumerate_possible_files(string|array $template_names, string $cache_key, $name)
    {
        // Remove empty entries.
        $template_names = array_filter((array) $template_names);
        $template_paths = $this->get_possible_template_folders();
        $abs_paths = [];

        foreach ($template_names as $template_name) {
            $template_name = ltrim($template_name, '/');
            foreach ($template_paths as $template_path) {
                $abs_paths[] = $template_path . $template_name;
            }
        }
        $abs_paths = $this->apply_filter(__FUNCTION__, $abs_paths, $cache_key, $template_names, $name);
        return $abs_paths;
    }

    protected function get_possible_template_folders()
    {
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
        // Sort the file paths based on priority.
        ksort($file_paths, SORT_NUMERIC);

        $file_paths = $this->apply_filter(__FUNCTION__, $file_paths);

        return array_map('trailingslashit', $file_paths);
    }
}
