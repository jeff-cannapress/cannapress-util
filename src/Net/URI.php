<?php

declare(strict_types=1);

namespace CannaPress\Util\Net;

use CannaPress\Retail\Imports\ApiUtil;

class URI
{
    public ?string $path;
    public ?string $host;
    public ?mixed $query;
    public ?string $fragment;
    public ?int $port;
    public ?string $scheme;
    public ?string $user_info;
    public ?string $original_string;

    public function __construct(mixed $url = null)
    {
        if (is_string($url)) {
            $this->scheme = parse_url($url, PHP_URL_SCHEME);
            $user = parse_url($url, PHP_URL_USER);
            $pass =  parse_url($url, PHP_URL_PASS);
            if (!empty($user) || !empty($pass)) {
                $this->user_info = implode(':', array_filter([$user, $pass]));
            }
            $this->host = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            if (!empty($port)) {
                $this->port = intval($port);
            } else {
                $this->port = null;
            }
            $this->path = rtrim(ltrim(parse_url($url, PHP_URL_PATH), '/'), '/');
            $this->query = parse_url($url, PHP_URL_QUERY);
            if (!empty($this->query)) {
                parse_str($this->query, $arr);
                if (!empty($arr)) {
                    $this->query = $arr;
                }
            }
            $this->fragment = parse_url($url, PHP_URL_FRAGMENT);
        }
        if (is_array($url) || is_object($url)) {
            ApiUtil::loadFromJson($this, $url);
        }
    }

    public function __toString()
    {
        $parts = [$this->scheme, '://'];
        if (!empty($this->user_info)) {
            $parts[] = $this->user_info;
            $parts[] = '@';
        }
        $parts[] = $this->host;
        if (!is_null($this->port)) {
            $parts[] = ':';
            $parts[] = $this->port;
        }
        if (!empty($this->path)) {
            $parts[] = '/';
            $parts[] = ltrim($this->path, '/');
        }
        if (!empty($this->query)) {
            $parts[] = '?';
            if (is_array($this->query)) {
                $parts[] = http_build_query($this->query);
            } else {
                $parts[] = $this->query;
            }
        }
        if (!empty($this->fragment)) {
            $parts[] = '#';
            $parts[] = $this->fragment;
        }
        return implode('', $parts);
    }
}
