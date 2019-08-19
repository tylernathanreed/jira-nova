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

            // Truncate the table
            static::query()->truncate();

            // Convert the labels into a subquery
            $query = DB::query()->fromSub($labels->reduce(function($query, $label) {

                $subquery = DB::query()->selectRaw("\"{$label}\" as name");

                return is_null($query) ? $subquery : $query->unionAll($subquery);

            }, null), 'labels');

            // Fill in the table with the new labels
            static::query()->insertUsing(['name'], $query);

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
}
