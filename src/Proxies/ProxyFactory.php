<?php

declare(strict_types=1);

namespace CannaPress\Util\Proxies;

use CannaPress\Util\Hashes;

class ProxyFactory
{
    public static $CACHE_DIRECTORY_PERMISSIONS = 0755;
    public static $CACHE_FILE_PERMISSIONS = 0644;

    public function __construct(protected string $cache_dir)
    {
        if (!is_dir($this->cache_dir)) {
            $old = umask(0);
            mkdir($this->cache_dir, self::$CACHE_DIRECTORY_PERMISSIONS, true);
            umask($old);
        }
    }


    public function create(string $exposed_class, object|null $target_instance, Interceptor $interceptor)
    {

        $target_kind = !is_null($target_instance) ? 'T' : 'U';
        $target_instance_name = $target_kind === 'T' ? get_class($target_instance) : ('INTERFACE_PROXY' . $exposed_class);

        $proxy_name = 'DynamicProxy' . (Hashes::fast(implode(',', [$exposed_class, $target_instance_name, $target_kind])));
        $generated_code_file = $this->cache_dir . '/' . $proxy_name . '.php';


        if (!class_exists($proxy_name)) {
            if (!file_exists(!$generated_code_file)) {
                $pg = new ProxyCodeGenerator($exposed_class, $proxy_name, $target_kind === 'T');
                $php = $pg->generate();
                file_put_contents($generated_code_file, $php);
                chmod($generated_code_file, self::$CACHE_FILE_PERMISSIONS);
            }
            require_once($generated_code_file);
        }

        if ($target_kind === 'T') {
            $result = new $proxy_name($target_instance, $interceptor);
        } else {
            $result = new $proxy_name($interceptor);
        }
        return $result;
    }
}
