<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleFocusAllocation extends Model
{
    use SoftDeletes;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_focus_allocations';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the schedule that this allocation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Returns the focus group that this allocation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function focusGroup()
    {
        return $this->belongsTo(FocusGroup::class, 'focus_group_id');
    }
}
