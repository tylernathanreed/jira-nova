<?php

namespace App\Nova\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

class CallMethod extends Action
{
    /**
     * The method to call on each model.
     *
     * @var array
     */
    public $method;

    /**
     * Creates a new action instance.
     *
     * @return $this
     */
    public function __construct($method, $name)
    {
        parent::__construct();

        $this->method = $method;
        $this->name = $name;
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection     $models
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $models->each->{$this->method}();
    }

    /**
     * Returns the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
