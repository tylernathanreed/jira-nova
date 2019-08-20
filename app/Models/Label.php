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
