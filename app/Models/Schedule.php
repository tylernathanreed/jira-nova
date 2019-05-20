<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;

    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedules';

    /**
     * Returns the template for weeks within this schedule that have not been defined.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function weekTemplate()
    {
        return $this->belongsTo(ScheduleWeekTemplate::class, 'week_template_id');
    }

    /**
     * Returns the weeks that belong to this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function weeks()
    {
        return $this->hasMany(ScheduleWeek::class, 'schedule_id');
    }
}
