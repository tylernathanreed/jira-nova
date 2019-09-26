<?php

namespace App\Models;

class WorkflowStatusGroup extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'workflow_status_groups';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the status types that belong to this status group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function statuses()
    {
        return $this->hasMany(WorkflowStatusType::class, 'status_group_id');
    }
}
