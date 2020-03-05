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
     * @param  string       $model
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
            \App\Models\Jira\User::class,
            \App\Models\Jira\Project::class,
            // \App\Models\Jira\Epic::class,
            // \App\Models\Jira\Issue::class,
            // \App\Models\Jira\Label::class,
            // \App\Models\Jira\IssueWorklog::class,
            // \App\Models\Jira\IssueChangelog::class,
            // \App\Models\Jira\Version::class
        ];

        // Determine the dependencies
        $dependencies = [
            \App\Models\Jira\Project::class => [\App\Models\Jira\User::class],
            // \App\Models\Jira\Epic::class => [\App\Models\Jira\Project::class, \App\Models\Jira\User::class],
            // \App\Models\Jira\Issue::class => [\App\Models\Jira\Project::class, \App\Models\Jira\User::class, \App\Models\Jira\Epic::class],
            // \App\Models\Jira\Label::class => [\App\Models\Jira\Issue::class],
            // \App\Models\Jira\IssueWorklog::class => [\App\Models\Jira\User::class, \App\Models\Jira\Issue::class],
            // \App\Models\Jira\IssueChangelog::class => [\App\Models\Jira\User::class, \App\Models\Jira\Issue::class],
            // \App\Models\Jira\Version::class => [\App\Models\Jira\Project::class, \App\Models\Jira\Issue::class],

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

        /*
        CacheJiraEndpoint::dispatchNow(\App\Models\Jira\User::class);
        CacheJiraEndpoint::dispatchNow(\App\Models\Jira\Project::class);
        */
    }
}