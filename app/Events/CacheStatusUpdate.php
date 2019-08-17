<?php

namespace App\Events;

class CacheStatusUpdate extends Event
{
    /**
     * The cache being updated.
     *
     * @var string
     */
    public $cache;

    /**
     * The operation being performed.
     *
     * @var string
     */
    public $operation;

    /**
     * Create a new event instance.
     *
     * @param  string  $cache
     * @param  string  $operation
     *
     * @return void
     */
    public function __construct($cache, $operation)
    {
        $this->cache = $cache;
        $this->operation = $operation;
    }
}
