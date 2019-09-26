<?php

namespace App\Models;

use Api;
use Closure;
use Carbon\Carbon;
use App\Support\Contracts\Cacheable;

class WorkflowStatusType extends Model implements Cacheable
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'workflow_status_types';

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
        // Determine the statuses
        $statuses = Api::getAllStatuses();

        // Determine the jira id to our id mapping
        $projects = Project::pluck('id', 'jira_id')->all();

        // Convert the statuses into our format
        $statuses = collect($statuses)->keyBy('id')->map(function($status) use ($projects) {

            $scope = optional($status->scope ?? null);

            return [
                'jira_id' => $status->id,
                'scope_type' => $scope->type == 'PROJECT' ? Project::class : null,
                'scope_id' => isset($scope->project->id) ? $projects[$scope->project->id] : null,
                'name' => $status->name,
                'description' => $status->description ?: null,
                'color' => $status->statusCategory->colorName,
            ];

        });

        // Enable mass assignment
        static::unguarded(function() use ($statuses) {

            // Update or create each status
            $statuses->each(function($status, $jira_id) {
                static::updateOrCreate(compact('jira_id'), $status);
            });

        });

        // Invoke the handler
        $callback(count($statuses), count($statuses));
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
        return count(Api::getAllStatuses());
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the scope of this workflow status type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function scope()
    {
        return $this->morphTo();
    }

    /**
     * Returns the group that this status type belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(WorkflowStatusGroup::class, 'status_group_id');
    }

    /**
     * Returns the seed group that this status type belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function groupFromSeed()
    {
        return $this->belongsTo(WorkflowStatusGroup::class, 'workflow_status_group_system_name', 'system_name');
    }
}
