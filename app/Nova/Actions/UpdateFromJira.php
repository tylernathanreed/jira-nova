<?php

namespace App\Nova\Actions;

use Laravel\Nova\Fields\Boolean;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

class UpdateFromJira extends Action
{
    /**
     * The toggleable options.
     *
     * @var array
     */
    public $options = [];

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
        // Iterate through each model
        foreach($models as $model) {

            // Update each model from jira
            $model->updateFromJira($model->jira(), $fields->toArray());

        }
    }

    /**
     * Returns the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        // Initialize the fields
        $fields = [];

        // Create a field for each option
        foreach($this->options as $key => $name) {
            $fields[] = Boolean::make($name, $key)->withMeta(['value' => 1]);
        }

        // Return the fields
        return $fields;
    }

    /**
     * Sets the toggleable options.
     *
     * @param  array  $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
