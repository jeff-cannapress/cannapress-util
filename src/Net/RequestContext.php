<?php

declare(strict_types=1);

namespace CannaPress\Util\Net;

use CannaPress\Util\Collections\KeyedCollection;
use CannaPress\Util\Collections\IndexedCollection;

class RequestContext
{
    public function domain(): string
    {
        return $this->_server['SERVER_NAME'];
    }
    public function method(): string
    {
        return strtoupper($this->_server['REQUEST_METHOD']);
    }
    public function path(): string
    {
        return ltrim($this->_server['REDIRECT_URL'] ?? "", '/\\');
    }
    private ?IndexedCollection $_path_segments = null;
    public function path_segments(): IndexedCollection
    {
        if (is_null($this->_path_segments)) {
            $this->_path_segments = IndexedCollection::direct(explode('/', untrailingslashit($this->path())));
        }
        return $this->_path_segments;
    }

    public KeyedCollection $query;
    public KeyedCollection $form;
    private KeyedCollection $_server;
    public KeyedCollection $items;
    public function __construct(KeyedCollection $_get, KeyedCollection $_post, KeyedCollection $_server)
    {
        $this->items = new KeyedCollection();
        $this->query = $_get;
        $this->form = $_post;
        $this->_server = $_server;
    }

    public static function from_globals(): RequestContext
    {
        return new RequestContext(KeyedCollection::direct($_GET), KeyedCollection::direct($_POST), KeyedCollection::direct($_SERVER));
    }
}
