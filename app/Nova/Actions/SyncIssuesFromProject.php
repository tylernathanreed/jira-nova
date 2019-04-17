<?php

namespace App\Nova\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

class SyncIssuesFromProject extends Action
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
        // Iterate through each project
        foreach($models as $project) {

            // Determine the jira issues
            $issues = $project->getUpdatedJiraIssues();

            // Sync the issues
            (new SyncIssueFromJira(compact('project')))->handleCollection([], $issues);

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
