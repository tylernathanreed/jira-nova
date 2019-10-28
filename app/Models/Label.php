<?php

namespace App\Models;

use DB;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class Label extends Model implements Cacheable
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'name';
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /////////////////////
    //* Weekly Labels *//
    /////////////////////
    /**
     * Returns the week label name.
     *
     * @param  mixed  $when
     *
     * @return string
     */
    public static function getWeekLabel($when = 'now')
    {
        // Convert the diff to a label
        return 'Week' . static::getWeekLabelIndex($when);
    }

    /**
     * Returns the week label epoch date.
     *
     * @return \Carbon\Carbon
     */
    public static function getWeekLabelEpoch()
    {
        return carbon('2019-07-07');
    }

    /**
     * Returns the week label index.
     *
     * @param  mixed  $when
     *
     * @return integer
     */
    public static function getWeekLabelIndex($when = 'now')
    {
        // Determine the first week reference
        $start = static::getWeekLabelEpoch();

        // Determine the current reference
        $when = carbon($when);

        // Return the week diff
        return $start->diffInWeeks($when);
    }

    /**
     * Returns the start and end date for the specified week.
     *
     * @param  integer  $index
     *
     * @return array
     */
    public static function getWeekRange($index)
    {
        // Determine the start date
        $start = static::getWeekLabelEpoch()->addWeeks($index)->addDay();

        // Determine the end date
        $end = $start->copy()->addDays(4);

        // Return the range
        return [$start, $end];
    }

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
        // Determine all of the labels
        $labels = static::getLabelsFromIssues();

        // Rebuild the labels within a transaction
        DB::transaction(function() use ($labels) {

            // Truncate the pivot table
            (new static)->issues()->newPivotStatement()->truncate();

            // Truncate the table
            static::query()->truncate();

            // Convert the labels into a subquery
            $query = DB::query()->fromSub($labels->reduce(function($query, $label) {

                $subquery = DB::query()->selectRaw("\"{$label}\" as name");

                return is_null($query) ? $subquery : $query->unionAll($subquery);

            }, null), 'labels');

            // Fill in the table with the new labels
            static::query()->insertUsing(['name'], $query);

            // Convert the pivot table into a subquery
            $query = static::query()->join('issues', function($join) {
                $join->on('issues.labels', 'like', DB::raw('"%""" || labels.name || """%"'));
            })->select([
                'issues.id as issue_id',
                'labels.name as label_name'
            ]);

            // Fill in the pivot table with new relations
            (new static)->issues()->newPivotStatement()->insertUsing(['issue_id', 'label_name'], $query);

        });

        // Invoke the handler
        $callback(count($labels), count($labels));
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
        return static::getLabelsFromIssues()->count();
    }

    /**
     * Returns all of the labels from the issues.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getLabelsFromIssues()
    {
        return Issue::where('labels', '!=', '[]')->select('labels')->distinct()->get()->pluck('labels')->collapse()->unique()->sort()->values()->toBase();
    }

    ///////////////
    //* Queries *//
    ///////////////
    /**
     * Creates and returns a new issue aggregates query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newIssueAggregatesQuery()
    {
        // Create a new query
        $query = $this->newQuery();

        // Join into issues
        $query->joinRelation('issues', function($join) {

            // Ignore completed issues
            $join->incomplete();

        });

        // Select the sum per label
        $query->select([
            'labels.name',
            DB::raw('max(issues.due_date) as due_date'),
            DB::raw('max(issues.estimate_date) as estimate_date'),
            DB::raw('count(*) as issues_remaining'),
            DB::raw(preg_replace('/\s\s+/', ' ', '
                sum(
                    case
                        when issues.estimate_remaining is null
                            then 3600
                        when issues.estimate_remaining < 3600
                            then 3600
                        else issues.estimate_remaining
                    end
                ) as estimate_remaining
            '))
        ])->groupBy('labels.name');

        // Return the query
        return $query;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the issues associated to this label.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issues_labels');
    }
}
