<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
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
    protected $table = 'schedules';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the week templates that govern this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function weekTemplates()
    {
        return $this->belongsToMany(ScheduleWeekTemplate::class, 'schedule_associations', 'schedule_id', 'schedule_week_template_id')->using(ScheduleAssociation::class);
    }

    /**
     * Returns the week template associations for this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function associations()
    {
        return $this->hasMany(ScheduleAssociation::class, 'schedule_id');
    }
}
