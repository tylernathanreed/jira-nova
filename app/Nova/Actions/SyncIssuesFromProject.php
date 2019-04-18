<?php

namespace App\Nova\Actions;

use App\Models\Issue;
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
            $jiras = $project->getUpdatedJiraIssues();

            // Create new models for each jira
            $issues = array_map(function($jira) use ($project) {

                // Find or create the issue
                $issue = Issue::firstOrCreate([
                    'jira_id' => $jira->id,
                    'jira_key' => $jira->key,
                    'project_id' => $project->id
                ]);

                // Update the issue timestamps
                $issue->touch();

                // Return the issue
                return $issue;

            }, $jiras);

            // Sync the issues
            (new SyncIssueFromJira)->setOptions(compact('project', 'jiras'))->handleCollection([], $issues);

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
