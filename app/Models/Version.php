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
            (new static)->issues()->newPivotStatement()->delete();

            // Truncate the table
            static::query()->delete();

            // Handle each version separate
            foreach($versions as $version) {

                // Create the version
                $instance = static::forceCreate($version);

                // Find the issues containing the version
                $issueIds = Issue::where('labels', '!=', '[]')->where('fix_versions', 'like', DB::raw("\"%\"\"{$instance->name}\"\"%\""))->pluck('id');

                // Create the pivot entries for the fix version
                $instance->issues()->sync($issueIds);

            }

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
     * Returns the issues associated to this version.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function issues()
    {
        return $this->belongsToMany(Issue::class, 'issues_fix_versions');
    }

    /**
     * Returns the release notes associated to this version.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function releaseNotes()
    {
        return $this->issues()->whereNotNull('issues.release_notes');
    }
}
