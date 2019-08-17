<?php

namespace App\Console\Commands;

use Jira;
use Event;
use App\Models\Issue;
use App\Models\Cache;
use Illuminate\Console\Command;
use App\Events\CacheStatusUpdate;

class JiraCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @option  {boolean}  all  Whether or not to cache all issues.
     *
     * @var string
     */
    protected $signature = 'jira:cache {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches the jira issues for metrics.';

    /**
     * Whether or not the listeners have been registered.
     *
     * @var boolean
     */
    protected $listenersRegistered = false;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Register the listeners if they haven't already been registered
        $this->registerListenersIfNotRegistered();

        // Handle the caches
        $this->handleCaches();

        /*
        // Make sure there's issues to cache
        if(($count = $this->newJiraCacheQuery()->count()) == 0) {

            $this->info('No issues to cache.');
            return;

        }

        // Indicate how many issues are getting cached
        $this->info("Caching {$count} issues...");

        // Cache the issues
        $this->cacheIssues();
        */
    }

    /**
     * Registers the listeners if they haven't already been registered.
     *
     * @return void
     */
    public function registerListenersIfNotRegistered()
    {
        // Make sure the listeners haven't already been registered
        if($this->listenersRegistered) {
            return;
        }

        // Register the listeners
        $this->registerListeners();

        // Mark the listeners as registered
        $this->listenersRegistered = true;
    }

    /**
     * Registers the listeners.
     *
     * @return void
     */
    public function registerListeners()
    {
        Event::listen(CacheStatusUpdate::class, function($event) {

            // Determine the cache and operation
            $cache = $event->cache;
            $operation = $event->operation;

            // Determine the model being cached
            $model = $cache->model_class;

            // Determine the record count and total
            $count = $cache->getAttribute("{$operation}_record_count");
            $total = $cache->getAttribute("{$operation}_record_total");

            // Determine the start and complete times
            $start = $cache->getAttribute("{$operation}_started_at");
            $complete = $cache->getAttribute("{$operation}_completed_at");

            // Nothing to report upon completion
            if(!is_null($complete)) {
                return;
            }

            // If the total is zero, then there's nothing to cache
            if($total == 0) {

                $this->info("[{$model}]: Nothing to cache.");
                return;

            }

            // If the count is zero, then we're just starting
            if($count == 0) {

                $this->info("[{$model}]: Caching {$total} records...");
                return;

            }

            // Determine the percent completion
            $percent = number_format(min($count, $total) / $total * 100, 2);

            // In all other cases, this is a progress update
            $this->info("[{$model}] -> Cached {$count} of {$total} records ({$percent}% complete).");

        });
    }

    /**
     * Handles the caches.
     *
     * @return void
     */
    public function handleCaches()
    {
        // Determine all of the caches
        $caches = Cache::all();

        // Determine the operation
        $operation = $this->option('all') ? 'rebuild' : 'recache';

        // Iterate through each cache
        foreach($caches as $cache) {
            $cache->{$operation}();
        }
    }

    /**
     * Caches the issues.
     *
     * @return void
     */
    public function cacheIssues()
    {
        $this->newJiraCacheQuery()->chunk(100, function($chunk, $page) {

            // Cache the chunk result
            $this->cacheChunkResult($chunk);

            // Log the status
            $this->logStatus($page, $chunk->count);

        });
    }

    /**
     * Creates and returns a new jira cache query.
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public function newJiraCacheQuery()
    {
        // Create a new query
        $query = Jira::newQuery();

        // Enforce an order by clause
        $query->orderBy('issuekey');

        // If we're loading all issues, return the query as-is
        if($this->option('all')) {
            return $query;
        }

        // Determine the previous cache date
        $date = $this->getPreviousCacheDate();

        // If we've never cached before, return the query as-is
        if(is_null($date)) {
            return $query;
        }

        // Exclude issues that we've already updated
        $query->where('updated', '>=', $date->toDateString());

        // Return the query
        return $query;
    }

    /**
     * Returns the previous cache date.
     *
     * @return \Carbon\Carbon|null
     */
    protected function getPreviousCacheDate()
    {
        // Determine the previous cache date
        $date = Issue::max('updated_at');

        // If no date exists, return null
        if(is_null($date)) {
            return null;
        }

        // Parse the date
        return carbon($date);
    }

    /**
     * Caches the specified chunk result.
     *
     * @param  \stdClass  $result
     *
     * @return void
     */
    protected function cacheChunkResult($result)
    {
        Issue::unguarded(function() use ($result) {

            $result->issues->keyBy('key')->map(function($issue) {

                $issue = array_except((array) $issue, [
                    'url',
                    'parent_url'
                ]);

                return $issue;

            })->each(function($issue, $key) {
                Issue::updateOrCreate(compact('key'), $issue);
            });

        });
    }

    /**
     * Logs the status of the specified page.
     *
     * @param  integer  $page
     * @param  integer  $total
     *
     * @return void
     */
    protected function logStatus($page, $total)
    {
        $this->info(sprintf('-> Cached %s of %s issues [%s complete].',
            min($page * 100, $total),
            $total,
            number_format(min($page * 100, $total) / $total * 100, 2) . '%'
        ));
    }
}
