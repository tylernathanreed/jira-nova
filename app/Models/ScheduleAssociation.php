<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleAssociation extends Model
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
    protected $table = 'schedule_associations';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_date',
        'end_date'
    ];

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the schedule in this association.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Returns the week template in this association.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function weekTemplate()
    {
        return $this->belongsTo(ScheduleWeekTemplate::class, 'schedule_week_template_id');
    }
}
