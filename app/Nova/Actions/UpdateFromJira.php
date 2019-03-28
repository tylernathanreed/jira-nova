<?php

namespace App\Nova\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

class UpdateFromJira extends Action
{
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
            $model->updateFromJira();

        }
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
