<?php

namespace App\Models;

use Jira;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model implements Cacheable
{
    use SoftDeletes;

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
        // Determine all of the projects
        $projects = Jira::projects()->getAllProjects();

        // Convert the projects into our format
        $projects = collect($projects)->keyBy('key')->map(function($project) {

            return [
                'jira_id' => $project->id,
                'jira_key' => $project->key,
                'name' => $project->name,
                'avatar_url' => $project->avatarUrls['32x32']
            ];

        });

        // Enable mass assignment
        static::unguarded(function() use ($projects) {

            // Update or create each project
            $projects->each(function($project, $jira_key) {

                // Update or create each project
                $project = static::updateOrCreate(compact('jira_key'), $project);

                // Associate the issues back to this project
                (new Issue)->newQuery()->getQuery()->where('key', 'like', $project->jira_key . '-%')->whereNull('project_id')->update([
                    'project_key' => $project->jira_key,
                    'project_id' => $project->id
                ]);

            });

        });

        // Invoke the handler
        $callback(count($projects), count($projects));
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
        return count(Jira::projects()->getAllProjects());
    }

    /////////////////
    //* Selection *//
    /////////////////
    /**
     * Returns the default selection value for this model.
     *
     * @return integer|null
     */
    public static function getDefaultSelectionValue()
    {
        return optional((new static)->newQuery()->where('jira_key', '=', config('jira.default-project'))->first())->getKey();
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the issues that belong to this project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function issues()
    {
        return $this->hasMany(Issue::class, 'project_id');
    }
}
