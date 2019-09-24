<?php

namespace App\Models;

use DB;
use Jira;
use Nova;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class Epic extends Model implements Cacheable
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    public $dates = [
        'due_date',
        'estimate_date'
    ];

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the internal url to this epic in Nova.
     *
     * @return string
     */
    public function getInternalUrl()
    {
        return static::getInternalUrlForId($this->id);
    }

    /**
     * Returns the internal url to the specified epic in Nova.
     *
     * @param  integer  $id
     *
     * @return string
     */
    public static function getInternalUrlForId($id)
    {
        return url(Nova::path() . '/resources/epics/' . $id);
    }

    /**
     * Returns the external url to this epic in Jira.
     *
     * @return string
     */
    public function getExternalUrl()
    {
        return static::getExternalUrlForKey($this->key);
    }

    /**
     * Returns the external url to the specified epic in Nova.
     *
     * @param  string  $key
     *
     * @return string
     */
    public static function getExternalUrlForKey($key)
    {
        return rtrim(config('jira.host'), '/') . '/browse/' . $key;
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
        // Determine the search expression
        $expression = is_null($since)
            ? 'issuetype = Epic'
            : ('issuetype = Epic and updated >= ' . $since->toDateString());

        // Determine all of the epics
        $epics = Jira::issues()->search($expression, 0, 100)->issues;

        // Determine the host endpoint
        $host = rtrim(config('jira.host'), '/');

        // Determine the field mapping
        $mapping = config('jira.fields');

        // Determine the projects
        $projects = Project::all()->pluck('id', 'jira_key')->all();

        // Convert the epics into our format
        $epics = collect($epics)->keyBy('key')->map(function($epic) use ($host, $mapping, $projects) {

            return [
                'project_key' => $key = $epic->fields->project->key,
                'project_id' => $projects[$key] ?? null,
                'key' => $epic->key,
                'url' => $host . '/browse/' . $epic->key,
                'name' => $epic->fields->{$mapping['epic_name']} ?? $epic->fields->summary,
                'color' => $epic->fields->{$mapping['epic_color']} ?? 'ghx-label-0',
                'summary' => $epic->fields->summary,
                'description' => $epic->fields->description,
                'active' => data_get($epic, 'fields.resolution.name') != 'Done' && data_get($epic, 'fields.status.name') != 'Done'
            ];

        });

        // Enable mass assignment
        static::unguarded(function() use ($epics) {

            // Update or create each epic
            $epics->each(function($epic, $key) {
                static::updateOrCreate(compact('key'), $epic);
            });

        });

        // Associate the issues back to this epic
        (new Issue)->newQuery()->getQuery()->whereNull('epic_id')->whereNotNull('epic_key')->update([
            'epic_id' => DB::raw('(select epics.id from epics where epics.key = issues.epic_key)')
        ]);

        // Update the epic names
        (new Issue)->newQuery()->getQuery()->whereNotNull('epic_key')->update([
            'epic_name' => DB::raw('(select epics.name from epics where epics.key = issues.epic_key)')
        ]);

        // Invoke the handler
        $callback(count($epics), count($epics));
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
        // Determine the search expression
        $expression = is_null($since)
            ? 'issuetype = Epic'
            : ('issuetype = Epic and updated >= ' . $since->toDateString());

        // Return the cache record count
        return Jira::issues()->search($expression, 0, 0)->total;
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

        // Select the sum per epic
        $query->select([
            'epics.name',
            DB::raw('max(issues.due_date) as due_date'),
            DB::raw('max(issues.estimate_date) as estimate_date'),
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
        ])->groupBy('epics.name');

        // Return the query
        return $query;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this epic belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the issues that belong to this epic.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function issues()
    {
        return $this->hasMany(Issue::class, 'epic_id');
    }
}
