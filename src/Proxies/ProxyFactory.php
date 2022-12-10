<?php

declare(strict_types=1);

namespace CannaPress\Util\Proxies;

class ProxyFactory
{
    public static $CACHE_DIRECTORY_PERMISSIONS = 0755;
    public static $CACHE_FILE_PERMISSIONS = 0644;

    private array $generated_proxies = [];
    private string $index_file;
    public function __construct(protected string $cache_dir)
    {
        $this->index_file = $this->cache_dir . '/proxies_list.json';
        $this->update_index_file();
    }
    private function update_index_file()
    {
        if (!is_dir($this->cache_dir)) {
            $old = umask(0);
            mkdir($this->cache_dir, self::$CACHE_DIRECTORY_PERMISSIONS, true);
            umask($old);
        }

        if (file_exists($this->index_file)) {
            $json = file_get_contents($this->index_file);
            $this->generated_proxies = json_decode($json, true);
        }
    }
    private function flush_index_file()
    {
        file_put_contents($this->index_file, json_encode($this->generated_proxies));
    }

    public function create(object|string $target, Interceptor $interceptor)
    {
        $target_class = is_string($target) ? $target : get_class($target);
        $target_type = is_object($target) ? 'T' : 'U';
        if (!isset($this->generated_proxies[$target_class]) || !isset($this->generated_proxies[$target_class][$target_type])) {
            $this->update_index_file();
            if (!isset($this->generated_proxies[$target_class]) || !isset($this->generated_proxies[$target_class][$target_type])) {
                $pg = new ProxyCodeGenerator($target_class, $target_type === 'T');
                $php = $pg->generate();
                $filename = $this->cache_dir . '/' . $pg->proxy_name . '.php';
                file_put_contents($filename, $php);
                chmod($filename, self::$CACHE_FILE_PERMISSIONS);
                $this->update_index_file();
                if (!isset($this->generated_proxies[$target_class])) {
                    $this->generated_proxies[$target_class] = [];
                }
                $this->generated_proxies[$target_class][$target_type] = $pg->proxy_full_name;
                $this->flush_index_file();
            }
        }
        if (isset($this->generated_proxies[$target_class]) && isset($this->generated_proxies[$target_class][$target_type]) && !class_exists($this->generated_proxies[$target_class][$target_type])) {
            $proxy_name = array_slice(explode('\\', $this->generated_proxies[$target_class][$target_type]), -1)[0];
            $filename = $this->cache_dir . '/' . $proxy_name . '.php';
            require_once($filename);
        }
        $proxy_name = $this->generated_proxies[$target_class][$target_type];
        if($target_type === 'T'){
            $result = new $proxy_name($target, $interceptor);
        }
        else{
            $result = new $proxy_name($interceptor);
        }
        return $result;
    }
}
