<?php

namespace App\Models;

use DB;
use Jira;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class Version extends Model implements Cacheable
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
        'release_date'
    ];

    /////////////
    //* Cache *//
    /////////////
    /**
     * Caches the versions.
     *
     * @param  \Closure             $callback
     * @param  \Carbon\Carbon|null  $since
     *
     * @return array
     */
    public static function runCacheHandler(Closure $callback, Carbon $since = null)
    {
        // Determine all of the versions
        $versions = static::getVersionsFromProjects();

        // Rebuild the versions within a transaction
        DB::transaction(function() use ($versions) {

            // Truncate the pivot table
            (new static)->issues()->newPivotStatement()->truncate();

            // Truncate the table
            static::query()->truncate();

            // Convert the versions into a subquery
            $query = DB::query()->fromSub($versions->reduce(function($query, $version) {

                $subquery = DB::query()->selectRaw(preg_replace('/\s\s+/', ' ', "
                    {$version['project_id']} as project_id,
                    {$version['jira_id']} as jira_id,
                    \"{$version['name']}\" as name,
                    " . ($version['released'] ? 1 : 0) . " as released,
                    " . (!is_null($version['release_date']) ? "\"{$version['release_date']}\"" : "null") . " as release_date
                "));

                return is_null($query) ? $subquery : $query->unionAll($subquery);

            }, null), 'versions');

            // Fill in the table with the new versions
            static::query()->insertUsing(['project_id', 'jira_id', 'name', 'released', 'release_date'], $query);

            // Convert the pivot table into a subquery
            $query = static::query()->join('issues', function($join) {
                $join->on('issues.fix_versions', 'like', DB::raw('"%""" || versions.name || """%"'));
            })->select([
                'issues.id as issue_id',
                'versions.id as version_id'
            ]);

            // Fill in the pivot table with new relations
            (new static)->issues()->newPivotStatement()->insertUsing(['issue_id', 'version_id'], $query);

        });

        // Invoke the handler
        $callback(count($versions), count($versions));
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
        return static::getVersionsFromProjects()->count();
    }

    /**
     * Returns all of the labels from the issues.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getVersionsFromProjects()
    {
        // Determine the projects
        $projects = Project::all()->keyBy('jira_id');

        // Determine the versions
        $versions = $projects->reduce(function($versions, $project) {
            return $versions->merge(Jira::projects()->getVersions($project->jira_key));
        }, collect());

        // Map the versions into an easily injestible format
        return $versions->map(function($version) use ($projects) {
            return [
                'project_id' => $projects[$version->projectId]->id,
                'jira_id' => $version->id,
                'name' => $version->name,
                'released' => (bool) $version->released,
                'release_date' => $version->releaseDate ? carbon($version->releaseDate)->toDateString() : null
            ];
        });
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
            'versions.id',
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
        ])->groupBy('versions.id');

        // Return the query
        return $query;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this version belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the issues associated to this label.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issues_fix_versions');
    }
}
