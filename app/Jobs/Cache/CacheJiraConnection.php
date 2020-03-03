<?php

namespace App\Jobs\Cache;

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
    	$this->dispatchEndpointJob(\App\Models\Jira\User::class);
    	$this->dispatchEndpointJob(\App\Models\Jira\Project::class);
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