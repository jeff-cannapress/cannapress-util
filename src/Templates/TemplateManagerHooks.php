<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

class TemplateManagerHooks
{
    public const filter_prefix = 'cannapress_template_manager';
    private static function name(...$parts)
    {
        $parts = [self::filter_prefix, ...$parts];
        return implode('_', $parts);
    }
    private static function apply($name, $value, ...$args)
    {
        if (function_exists('apply_filters')) {
            return apply_filters(self::name($name), $value, ...$args);
        }
    }
    private static function do($name,  ...$args)
    {
        if (function_exists('do_action')) {
            return do_action(self::name($name), ...$args);
        }
    }
    public static function get_template_factory_identifier(string $identifier, string $name): string
    {
        return self::apply(__FUNCTION__, $identifier, $name);
    }
    /**
     * Filters the absolute file name calculation
     * @param string|null|false $file_name the name of the file resolved
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function get_absolute_filename(string|null|false $file_name, string $part_name, array $extensions): string|null|false
    {
        return self::apply(__FUNCTION__, $file_name, $part_name, $extensions);
    }
    /**
     * Filter to bypass the absolute file name calculation -- return a value to bypass the calculation and resolution
     * @param string|null|false $file_name the name of the file resolved -- always null
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function before_get_absolute_filename(string|null|false $file_name, string $part_name, array $extensions): string|null|false
    {
        return self::apply(__FUNCTION__, $file_name, $part_name, $extensions);
    }

    /**
     * Filters the calculation of all possible template file paths
     * @param array $all_paths all the calculated paths for the part_name and extensions
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function get_possible_template_paths(array $all_paths, string $part_name, array $extensions): array
    {
        return self::apply(__FUNCTION__, $all_paths, $part_name, $extensions);
    }
    /**
     * bypasses the calculation of all possible template file paths -- return non-empty to bypass calculation of file paths
     * @param array|null $all_paths all the calculated paths for the part_name and extensions -- always empty
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function before_get_possible_template_paths(array|null $all_paths, string $part_name, array $extensions): array|null
    {
        return self::apply(__FUNCTION__, $all_paths, $part_name, $extensions);
    }
    /**
     * Filters the calculation of all possible template file names
     * @param array $all_paths all the calculated paths for the part_name and extensions
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function get_possible_file_names(array|null $possible_file_names, string $part_name, array $extensions): array|null
    {
        return self::apply(__FUNCTION__, $possible_file_names, $part_name, $extensions);
    }
    /**
     * Filters the calculation of all possible template file names -- return non-empty to bypass
     * @param array $all_paths always null
     * @param string $part_name the name of the requested template part
     * @param array $extensions the list of valid file extensions for this part in priority order
     */
    public static function before_get_possible_file_names(array|null $possible_file_names, string $part_name, array $extensions): array|null
    {
        return self::apply(__FUNCTION__, $possible_file_names, $part_name, $extensions);
    }

    /**
     * filters the theme overrides folder name for the plugin
     * @param string $theme_overrides_folder the overrides folder name
     * @param string $plugin_dir the absoulte path of the plugin using the template manager
     * @param string $which the instance of the template manager if named
     */
    public static function get_theme_overrides_folder(string $theme_overrides_folder, string $plugin_dir, string $which): string
    {
        return self::apply(__FUNCTION__, $theme_overrides_folder, $plugin_dir, $which);
    }

    /**
     * Filter the list of directories to search for template files
     * @param array $possible_paths the list of paths to search
     */
    public static function get_possible_template_directories(array $possible_paths): array
    {
        return self::apply(__FUNCTION__, $possible_paths);
    }


    /**
     * bypass filter for the list of directories to search for template files -- return non-empty to bypass
     * @param array $possible_paths the list of paths to search
     */
    public static function before_get_possible_template_directories(array $possible_paths): array
    {
        return self::apply(__FUNCTION__, $possible_paths);
    }

    /**
     * Filter the instance properties for a template about to be instantiated
     * @param array $instance_props
     * @param string $identifier
     * @param string $file_name
     * @param TemplateInstanceFactory $factory
     */
    public static function get_instance_props(array $instance_props, string $identifier, string $file_name, TemplateInstanceFactory $factory): array
    {
        return self::apply(__FUNCTION__, $instance_props, $identifier, $file_name, $factory);
    }
    /**
     * Bypass filter / action for when a template is about to be rendered out to the buffer.
     * @param bool $should_emit -- always true
     * @param string $identifier
     * @param string $file_name
     * @param object $template
     */
    public static function should_do_emit(bool $should_emit, string $identifier, string $file_name, object $template): bool
    {
        return self::apply(__FUNCTION__, $should_emit, $identifier, $file_name, $template);
    }

    /**
     * filter the rendered html for a template
     * @param string $html the rendred html
     * @param string $identifier
     * @param string $file_name
     * @param object $template
     */
    public static function template_instance_rendered(string $html, string $identifier, string $file_name, object $template): string
    {
        return self::apply(__FUNCTION__, $html, $identifier, $file_name, $template);
    }
    /**
     * bypass filter the rendered html for a template -- return non-empty to bypass rendrering
     * @param string $html always ""
     * @param string $identifier
     * @param string $file_name
     * @param object $template
     */
    public static function before_template_instance_rendered(string $html, string $identifier, string $file_name, object $template): string
    {
        return self::apply(__FUNCTION__, $html, $identifier, $file_name, $template);
    }


    /**
     * action to fire after the buffer has opened but before the template file is included 
     * @param string $identifier
     * @param string $file_name
     * @param object $template
     */
    public static function before_template_file_included(string $identifier, string $file_name, object $template): void
    {
        self::do(__FUNCTION__, $identifier, $file_name, $template);
    }
    /**
     * action to fire after the template file is included but before the buffer is closed
     * @param string $identifier
     * @param string $file_name
     * @param object $template
     */
    public static function after_template_file_included(string $identifier, string $file_name, object $template): void
    {
        self::do(__FUNCTION__, $identifier, $file_name, $template);
    }
}
