<?php

namespace App\Models;

use DB;
use Api;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class IssueWorklog extends Model implements Cacheable
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'started_at'
    ];

    /////////////
    //* Cache *//
    /////////////
    /**
     * Caches the issues.
     *
     * @param  \Closure             $callback
     * @param  \Carbon\Carbon|null  $since
     *
     * @return array
     */
    public static function runCacheHandler(Closure $callback, Carbon $since = null)
    {
        // Enable mass assignment
        static::unguarded(function() use ($callback, $since) {

            // Determine the initial cache count
            $count = static::getCacheRecordCount($since);

            // Initialize the number of processed records
            $processed = 0;

            // Iterate through the pages to cache
            static::newCacheQuery($since)->chunkById(10, function($issues) use ($callback, $count, &$processed) {

                // Determine the user mapping
                $userMapping = User::all()->pluck('id', 'jira_key');

                // Iterate through each issue
                foreach($issues as $issue) {

                    // Handle each issue in a database transaction
                    DB::transaction(function() use ($issue, $userMapping) {

                        // Determine the worklog page offset
                        $offset = $issue->worklogs_count ?? 0;

                        // Determine the total number of worklogs
                        $total = Api::getIssueWorklogs($issue->key, ['maxResults' => 0])->total;

                        // Walk through the change logs
                        while($offset < $total) {

                            // Determine the next page of worklogs
                            $worklogs = Api::getIssueWorklogs($issue->key, [
                                'startAt' => $offset,
                                'maxResults' => 100
                            ])->worklogs;

                            // Increase the offset
                            $offset += 100;

                            // Create each worklog
                            foreach($worklogs as $worklog) {

                                // Create the worklog
                                $newWorklog = $issue->worklogs()->updateOrCreate([
                                    'jira_id' => $worklog->id,
                                ], [
                                    'jira_id' => $worklog->id,
                                    'author_id' => isset($worklog->author->key) ? ($userMapping[$worklog->author->key] ?? null) : null,
                                    'author_key' => $worklog->author->key ?? null,
                                    'author_name' => $worklog->author->displayName ?? ($worklog->author->name ?? ($worklog->author->key ?? null)),
                                    'author_icon_url' => $worklog->author->avatarUrls->{'32x32'} ?? null,
                                    'time_spent' => $worklog->timeSpentSeconds,
                                    'started_at' => carbon($worklog->started)->tz('America/Chicago')->toDateTimeString()
                                ]);

                            }

                        }

                        // Update the worklog total
                        $issue->worklogs_count = $total;
                        $issue->worklogs_updated_at = carbon('+1 second');

                        // Save the issue
                        $issue->timestamps = false;
                        $issue->save();
                        $issue->timestamps = true;

                    });

                }

                // Increase the number of processed records
                $processed += $issues->count();

                // Invoke the handler
                $callback($processed, $count);

            });

        });
    }

    /**
     * Returns the number of records that need to be cached.
     *
     * @param  \Carbon\Carbon|null  $since
     *
     * @return integer
     */
    public static function getCacheRecordCount(Carbon $since = null)
    {
        return static::newCacheQuery($since)->count();
    }

    /**
     * Creates and returns a new cache query.
     *
     * @param  \Carbon\Carbon|null  $since
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function newCacheQuery(Carbon $since = null)
    {
        return (new Issue)->newQuery()->where(function($query) {

            // If the worklogs have never been updated, we need to cache it
            $query->whereNull('worklogs_updated_at');

            // If the issue has been updated after the worklogs were updated, we need to cache it
            $query->orWhereColumn('worklogs_updated_at', '<', 'updated_at');

        });
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the issue that this worklog belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * Returns the user that this worklog belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
