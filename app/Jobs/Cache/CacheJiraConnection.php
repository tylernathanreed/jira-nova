<?php

namespace App\Jobs\Cache;

use Process;
use App\Jobs\Job;

class CacheJiraConnection extends Job
{
    /**
     * The api connection name.
     *
     * @var string|null
     */
    public $apiConnection;

    /**
     * Creates a new job instance.
     *
     * @param  string|null  $apiConnection
     *
     * @return void
     */
    public function __construct($apiConnection = null)
    {
        $this->apiConnection = $apiConnection;
    }

    /**
     * Handles this job.
     *
     * @return void
     */
    public function handle()
    {
        // Determine the models to cache
        $models = [
            \App\Models\Jira\Component::class,
            \App\Models\Jira\IssueType::class,
            \App\Models\Jira\Project::class,
            \App\Models\Jira\ProjectIssueStatusType::class,
            \App\Models\Jira\ProjectIssueType::class,
            \App\Models\Jira\User::class,
            \App\Models\Jira\Version::class,
            \App\Models\Jira\WorkflowStatusCategory::class,
            \App\Models\Jira\WorkflowStatusType::class
        ];

        // Determine the dependencies
        $dependencies = [
            \App\Models\Jira\Project::class => [\App\Models\Jira\User::class],
            \App\Models\Jira\Component::class => [\App\Models\Jira\Project::class],
            \App\Models\Jira\Version::class => [\App\Models\Jira\Project::class],
            \App\Models\Jira\WorkflowStatusType::class => [\App\Models\Jira\WorkflowStatusCategory::class],
            \App\Models\Jira\ProjectIssueType::class => [\App\Models\Jira\Project::class, \App\Models\Jira\IssueType::class],
            \App\Models\Jira\ProjectIssueStatusType::class => [\App\Models\Jira\ProjectIssueType::class, \App\Models\Jira\WorkflowStatusType::class],
        ];

        // Create the jobs for each model
        foreach($models as $model) {
            $jobs[$model] = new CacheJiraEndpoint($model, $this->apiConnection);
        }

        // Enforce the dependencies
        foreach($dependencies as $then => $processDependencyKeys) {

            // Enforce each dependency
            foreach($processDependencyKeys as $first) {
                $jobs[$then]->processAfter($jobs[$first]);
            }

        }

        // Mark all of the jobs as ready to process
        foreach($jobs as $model => $job) {
            $job->processWhenReady();
        }

    	// $this->dispatchEndpointJob(\App\Models\Jira\User::class);
    	// $this->dispatchEndpointJob(\App\Models\Jira\Project::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\Component::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\Version::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\IssueType::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\WorkflowStatusCategory::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\WorkflowStatusType::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\ProjectIssueType::class);
        // $this->dispatchEndpointJob(\App\Models\Jira\ProjectIssueStatusType::class);
    }

    /**
     * Dispatches a job to handle the specified page.
     *
     * @param  \stdClass|array  $page
     *
     * @return void
     */
    public function dispatchEndpointJob($model)
    {
        CacheJiraEndpoint::dispatch($model, $this->apiConnection);
    }
}