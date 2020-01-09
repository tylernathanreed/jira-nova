<?php

namespace App\Jobs\Cache;

use App\Jobs\Job;
use Illuminate\Support\Arr;

class CacheJiraPage extends Job
{
    /**
     * The model this job corresponds to.
     *
     * @var string
     */
    public $model;

    /**
     * The page this job should cache.
     *
     * @var string|null
     */
    public $page;

    /**
     * Creates a new job instance.
     *
     * @param  string           $model
     * @param  \stdClass|array  $page
     *
     * @return void
     */
    public function __construct($model, $page)
    {
        $this->model = $model;
        $this->page = $page;
    }

    /**
     * Handles this job.
     *
     * @return void
     */
    public function handle()
    {
        // Determine the records that need to be created or updated, keyed by their cache value
        $records = $this->getDirtyRecords();

        // Now that we have a list of new or updated records, we'll want to
        // separate the list into records that need to be created, versus
        // records that need to be updated. We'll need keys do to that.

        // Split the records into the ones that need to be created versus updated
        [$toCreate, $toUpdate] = $this->splitDirtyRecordsByAction($records);

        // Perform the creations
        foreach($toCreate as $cacheKey => $record) {
            $this->createFromJira($record, $cacheKey);
        }

        // Perform the updates
        foreach($toUpdate as $cacheKey => $pair) {

            // Extract the pair
            [$record, $model] = $pair;

            // Update the model
            $this->updateFromJira($model, $record);

        }
    }

    /**
     * Creates and returns a new model from the specified record.
     *
     * @param  \stdClass  $record
     * @param  string     $cacheKey
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createFromJira($record, $cacheKey)
    {
        // Create a new model instance
        $model = $this->newModel();

        // Assign the cache key
        $model->setJiraCacheKey($cacheKey);

        // Update the model instance
        return $this->updateFromJira($model, $record);
    }

    /**
     * Updates and returns the specified model using the given record.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \stdClass                            $record
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateFromJira($model, $record)
    {
        // Assign the cache value
        $model->setJiraCacheValue($model::getJiraCacheValueFromApi($record));

        // Update the model
        return $model->updateFromJira($record);
    }

    /**
     * Splits the specified records by those that need to be created versus updated.
     *
     * @param  array  $records
     *
     * @return array
     */
    public function splitDirtyRecordsByAction($records)
    {
        // Our first step in this process will be to take the given records,
        // which are keyed by their cache value, and extract their cache
        // key. If the cache key doesn't exist, then the record is new.

        // Determine the cache keys
        $keys = array_map(function($record) {
            return $this->getJiraCacheKeyFromApi($record);
        }, $records);

        // Key the records by their cache key
        $records = array_combine($keys, $records);

        // Now we essentially have an array that pairs cache values (as keys)
        // and cache keys (as values). Existing keys should be updated, and
        // new keys should be created. This should be easy to figure out.

        // Find the existing models by their cache key
        $existing = $this->getModelsByCacheKey($keys)->all();

        // The update list needs to contain both the model and record
        $toUpdate = array_combine(array_keys($existing), array_map(function($model) use ($records) {
            return [$records[$model->getJiraCacheKey()], $model];
        }, $existing));

        // All remaining records not flagged for updating should be created
        $toCreate = Arr::except($records, array_keys($toUpdate));

        // Return the split records
        return [$toCreate, $toUpdate];
    }

    /**
     * Returns the specified models keyed by their cache key.
     *
     * @param  array  $keys
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getModelsByCacheKey($keys)
    {
        // Create a new model instance
        $instance = $this->newModel();

        // Determine the stored cache key name
        $attribute = $instance->getJiraCacheKeyName();

        // Create a new query
        $query = $instance->newQuery();

        // Filter by the cache key
        $query->whereIn($attribute, $keys);

        // Return the results, keyed by the cache key
        return $query->get()->keyBy($attribute);
    }

    /**
     * Returns the records that need to be created or updated.
     *
     * @return array
     */
    public function getDirtyRecords()
    {
        // The first optimization that we will perform is to leverage the cache
        // value for each record. Since the value captures the record state,
        // anything that matches our database hasn't been changed at all.

        // Extract the records from the page, keyed by cache value
        $records = $this->getMappedRecords();

        // Create a new model instance
        $instance = $this->newModel();

        // Find the cache values that already exist (these haven't been changed)
        $exists = $instance->newQuery()->whereIn($instance->getJiraCacheValueName(), array_keys($records))->pluck($instance->getKeyName())->all();

        // Exclude records that already match as-is
        return Arr::except($records, $exists);
    }

    /**
     * Returns the records from the provided page, keyed by their cache value.
     *
     * @return array
     */
    public function getMappedRecords()
    {
        // Extract the records from the page
        $records = $this->getRecords();

        // Determine the hashes from the records
        $hashes = array_map(function($record) {
            return $this->getJiraCacheValueFromApi($record);
        }, $records);

        // Key the records by their hash
        return array_combine($hashes, $records);
    }

    /**
     * Returns the records from the provided page.
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->page;
    }

    /**
     * Creates and returns a fresh instance of the model represented by the resource.
     *
     * @return mixed
     */
    public function newModel()
    {
        $model = $this->model;

        return new $model;
    }

    /**
     * Returns the jira cache key from the specified api record.
     *
     * @return string
     */
    public function getJiraCacheKeyFromApi($record)
    {
        $model = $this->model;

        return $model::getJiraCacheKeyFromApi($record);
    }

    /**
     * Returns the jira cache value from the specified api record.
     *
     * @return string
     */
    public function getJiraCacheValueFromApi($record)
    {
        $model = $this->model;

        return $model::getJiraCacheValueFromApi($record);
    }
}