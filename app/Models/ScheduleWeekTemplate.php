<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleWeekTemplate extends Model
{
    use SoftDeletes;

    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_week_templates';

    /**
     * Returns the schedules that use this week template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'week_template_id');
    }

    /**
     * Returns the day templates that belong to this week template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dayTemplates()
    {
        return $this->hasMany(ScheduleDayTemplate::class, 'week_template_id');
    }

    /**
     * Returns the weeks that belong to this week template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function weeks()
    {
        return $this->hasMany(ScheduleWeek::class, 'week_template_id');
    }
}
