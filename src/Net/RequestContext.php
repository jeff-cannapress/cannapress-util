<?php

//declare(strict_types=1);

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
    public function __construct(?KeyedCollection $_get = null, ?KeyedCollection $_post= null, ?KeyedCollection $_server= null)
    {
        $this->items = new KeyedCollection();
        $this->query = $_get ?? KeyedCollection::direct($_GET);
        $this->form = $_post ?? KeyedCollection::direct($_POST);
        $this->_server = $_server ??  KeyedCollection::direct($_SERVER);
    }
}
