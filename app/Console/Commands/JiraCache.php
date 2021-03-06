<?php

namespace App\Console\Commands;

use DB;
use Jira;
use Event;
use App\Models\Issue;
use App\Models\Cache;
use App\Jobs\Cache\CacheJob;
use Illuminate\Console\Command;
use App\Events\CacheStatusUpdate;

class JiraCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @option  {boolean}  "all"    Whether or not to cache all issues.
     * @option  {boolean}  "reset"  Whether or not clear all cached information.
     *
     * @var string
     */
    protected $signature = 'jira:cache {--all} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Caches the jira issues for metrics';

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
        // Clear cache information if requested
        $this->clearCacheDataIfRequested();

        // Register the listeners if they haven't already been registered
        $this->registerListenersIfNotRegistered();

        // Handle the caches
        $this->handleCaches();
    }

    /**
     * Clears the cache data if requested by the user.
     *
     * @return void
     */
    public function clearCacheDataIfRequested()
    {
        // Make sure the action was requested
        if(!$this->option('reset')) {
            return;
        }

        // Clear the cache data
        $this->clearCacheData();
    }

    /**
     * Clears the cache data.
     *
     * @return void
     */
    public function clearCacheData()
    {
        $this->comment("Clearing Labels...");
        DB::table('issues_labels')->delete();
        DB::table('labels')->delete();
        $this->info("Cleared Labels.");

        $this->comment("Clearing Versions...");
        DB::table('issues_fix_versions')->delete();
        DB::table('versions')->delete();
        $this->info("Cleared Versions.");

        $this->comment("Clearing Worklogs...");
        DB::table('issue_worklogs')->delete();
        $this->info("Cleared Worklogs.");

        $this->comment("Clearing Changelogs...");
        DB::table('issue_changelog_items')->delete();
        DB::table('issue_changelogs')->delete();
        $this->info("Cleared Changelogs.");

        $this->comment("Clearing Issues...");
        DB::table('issues')->delete();
        $this->info("Cleared Issues.");

        $this->comment("Clearing Epics...");
        DB::table('epics')->delete();
        $this->info("Cleared Epics.");

        $this->comment("Clearing Projects...");
        DB::table('projects')->delete();
        $this->info("Cleared Projects.");
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

            // Make sure the count doesn't exceed the total
            $count = min($count, $total);

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
        $caches = Cache::orderBy('execution_order')->get();

        // Determine the operation
        $operation = $this->option('all') ? 'rebuild' : 'recache';

        // Iterate through each cache
        foreach($caches as $cache) {
            dispatch((new CacheJob($cache, $operation))->onConnection('sync'));
        }
    }
}
