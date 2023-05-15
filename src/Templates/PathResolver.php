<?php

declare(strict_types=1);

namespace CannaPress\Util\Templates;

use CannaPress\Util\TransientCache;
use CannaPress\Util\Container;

class PathResolver
{

    protected FileResolver $files;
    protected TransientCache $path_cache;
    public function __construct(
        protected DirectoryResolver $dirs,
        FileResolver|null $files = null,
        TransientCache|null $path_cache = null
    ) {
        $this->files = $files ?? new FileResolver();
        $this->path_cache = $path_cache ?? new TransientCache(TemplateManager::filter_prefix);
    }
    public function child(string $path)
    {
        return new PathResolver($this->dirs->child_resolver($path), $this->files, $this->path_cache);
    }

    public function get_absolute_filename(string $name, array $extensions = ['php', 'html']): string|null
    {


        if (empty($file_name)) {
            $cache_key = $name . '.' . (implode('|', $extensions));
            $file_name = $this->path_cache->get($cache_key);
            if (is_null($file_name)) {
                $possible_paths = $this->get_possible_template_paths($name, $extensions);
                $file_name = self::find_first_existing_file($possible_paths);
                if (!empty($file_name)) {
                    $this->path_cache->set($cache_key, $file_name, 60 * 5/* 5min */);
                }
            }
        }


        if (false === $file_name) {
            return "";
        }
        return $file_name;
    }
    public function get_template_identifier(string $name): string
    {
        $identifier =  $this->dirs->get_template_identifier($name);
        return $identifier;
    }
    public static function name(string $name)
    {
        return self::class . '/' . $name;
    }
    public static function find_first_existing_file(array $possible_paths)
    {
        foreach ($possible_paths as $file_name) {
            if (file_exists($file_name)) {
                return $file_name;
            }
        }
        return false;
    }

    public function get_possible_template_paths(string $name, array $extensions)
    {
        $all_paths = [];
        $file_names = $this->files->get_possible_file_names($name, $extensions);
        $directories = $this->dirs->get_possible_template_directories();

        foreach ($directories as $dir) {
            foreach ($file_names as $file) {
                $all_paths[] = $dir . $file;
            }
        }

        return $all_paths;
    }
}
