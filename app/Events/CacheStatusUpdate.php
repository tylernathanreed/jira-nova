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
     * The operation being performed.
     *
     * @var string
     */
    public $operation;

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
     * @param  string   $operation
     * @param  integer  $current
     * @param  integer  $total
     *
     * @return void
     */
    public function __construct($model, $operation, $current, $total)
    {
        $this->model = $model;
        $this->operation = $operation;
        $this->current = $current;
        $this->total = $total;
    }
}
