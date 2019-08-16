<?php

namespace App\Events;

class CacheStatusUpdate extends Event
{
    /**
     * The model cache that was updated.
     *
     * @var string
     */
    public $model;

    /**
     * The number of issues that have already been updated.
     *
     * @var integer
     */
    public $current;

    /**
     * The total number of issues that will be updated.
     *
     * @var integer
     */
    public $total;

    /**
     * Create a new event instance.
     *
     * @param  string   $model
     * @param  integer  $current
     * @param  integer  $total
     *
     * @return void
     */
    public function __construct($model, $current, $total)
    {
        $this->model = $model;
        $this->current = $current;
        $this->total = $total;
    }
}
