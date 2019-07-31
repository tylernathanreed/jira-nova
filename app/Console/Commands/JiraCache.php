<?php

namespace App\Console\Commands;

use Jira;
use App\Models\Issue;
use Illuminate\Console\Command;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Make sure there's issues to cache
        if(($count = $this->newJiraCacheQuery()->count()) == 0) {

            $this->info('No issues to cache.');
            return;

        }

        // Indicate how many issues are getting cached
        $this->info("Caching {$count} issues...");

        // Cache the issues
        $this->cacheIssues();
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
