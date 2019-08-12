<?php

namespace App\Models\Views;

use App\Models\Schedule;
use App\Models\FocusGroup;

class ScheduleFocusDailyAllocation extends View
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'vw_schedule_focus_daily_allocations';

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
