<?php

namespace App\Jobs\Cache;

use App\Jobs\Job;
use Reedware\LaravelApi\ApiManager;

class CacheJiraEndpoint extends Job
{
    /**
     * The model this job corresponds to.
     *
     * @var string
     */
    public $model;

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
    public function __construct($model, $apiConnection = null)
    {
        $this->model = $model;
        $this->apiConnection = $apiConnection;
    }

    /**
     * Handles this job.
     *
     * @return void
     */
    public function handle(ApiManager $api)
    {
        // Determine the api connection
        $connection = $api->connection($this->apiConnection);

        // Extract the pages from the connection
        $pages = $this->getPaginatedRecords($connection);

        // Handle each page as a separate job
        foreach($pages as $page) {
            $this->dispatchPageJob($page);
        }
    }

    /**
     * Returns the paginated records using the specified connection.
     *
     * @param  \App\Support\Jira\Api\Connection  $connection
     *
     * @return array
     */
    public function getPaginatedRecords($connection)
    {
        $model = $this->model;

        return $model::getPaginatedJiraRecords($connection);
    }

    /**
     * Dispatches a job to handle the specified page.
     *
     * @param  \stdClass|array  $page
     *
     * @return void
     */
    public function dispatchPageJob($page)
    {
        CacheJiraPage::dispatch($this->model, $page);
    }

    /**
     * Creates and returns a fresh instance of the model represented by the resource.
     *
     * @return mixed
     */
    public static function newModel()
    {
        $model = $this->model;

        return new $model;
    }
}