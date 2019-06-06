<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleWeekTemplate extends Model
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
    protected $table = 'schedule_week_templates';

    /**
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        'allocations' => 'json'
    ];

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the schedules that use this week template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'schedule_associations', 'schedule_week_template_id', 'schedule_id')->using(ScheduleAssocation::class);
    }

    /**
     * Returns the schedule associations for this week template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function associations()
    {
        return $this->hasMany(ScheduleAssociation::class, 'schedule_week_template_id');
    }
}
