<?php

namespace App\Models;

use DB;
use Api;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class IssueChangelog extends Model implements Cacheable
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at'
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

                // Determine the list of fields that use non-string values
                $nonStringFields = static::getNonStringFieldNames();

                // Iterate through each issue
                foreach($issues as $issue) {

                    // Handle each issue in a database transaction
                    DB::transaction(function() use ($issue, $userMapping, $nonStringFields) {

                        // Determine the changelog page offset
                        $offset = $issue->changelogs_count ?? 0;

                        // Determine the total number of changelogs
                        $total = Api::getChangeLogs($issue->key, ['maxResults' => 0])->total;

                        // Walk through the change logs
                        while($offset < $total) {

                            // Determine the next page of changelogs
                            $changelogs = Api::getChangeLogs($issue->key, [
                                'startAt' => $offset,
                                'maxResults' => 100
                            ])->values;

                            // Increase the offset
                            $offset += 100;

                            // Create each changelog
                            foreach($changelogs as $changelog) {

                                // Create the changelog
                                $newChangelog = $issue->changelogs()->create([
                                    'jira_id' => $changelog->id,
                                    'issue_key' => $issue->key,
                                    'author_id' => $userMapping[$changelog->author->key] ?? null,
                                    'author_key' => $changelog->author->key ?? null,
                                    'author_name' => $changelog->author->name ?? null,
                                    'author_icon_url' => $changelog->author->avatarUrls->{'32x32'} ?? null,
                                    'created_at' => carbon($changelog->created)->tz('America/Chicago')->toDateTimeString()
                                ]);

                                // Add each item to the changelog
                                foreach($changelog->items as $index => $item) {

                                    // The general rule of thumb is that if both string versions
                                    // of the item exist, that's the one we'll be using. There
                                    // are some exceptions to this, which we'll get to later.

                                    // Determine whether or not we're using the string values
                                    $useStringValues = !empty($item->fromString) && !empty($item->toString);

                                    // If think we are going to use string values, we need to
                                    // check against our list of exceptions that require us
                                    // to instead use the non-string values. Easy peasy.

                                    // If we're using string values, make sure that's okay
                                    if(in_array($item->field, $nonStringFields)) {
                                        $useStringValues = false;
                                    }

                                    // Determine the new and old values
                                    $from = $useStringValues ? $item->fromString : $item->from;
                                    $to = $useStringValues ? $item->toString : $item->to;

                                    // Create the item
                                    $newItem = $newChangelog->items()->create([
                                        'item_index' => $index,
                                        'item_field_name' => $item->field,
                                        'item_from' => $from,
                                        'item_to' => $to,
                                    ]);

                                }

                            }

                        }

                        // Update the changelog total
                        $issue->changelogs_count = $total;
                        $issue->changelogs_updated_at = carbon('+1 second');

                        // Save the issue
                        $issue->save();

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

            // If the changelogs have never been updated, we need to cache it
            $query->whereNull('changelogs_updated_at');

            // If the issue has been updated after the changelogs were updated, we need to cache it
            $query->orWhereColumn('changelogs_updated_at', '<', 'updated_at');

        });
    }

    /**
     * Returns the list of field names that use non string changelog values.
     *
     * @return array
     */
    public static function getNonStringFieldNames()
    {
        return [
            'duedate',
            'Estimated Completion Date',
            'Target Date',
            'Date Tested'
        ];
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the issue that this changelog belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function issue()
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * Returns the user that this changelog belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsToMany(User::class, 'author_id');
    }

    /**
     * Returns the items that belong to this changelog.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(IssueChangelogItem::class, 'issue_changelog_id');
    }
}
