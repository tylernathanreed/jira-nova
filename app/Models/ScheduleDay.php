<?php

namespace App\Models;

class ScheduleDay extends Model
{
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_days';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
    	'date'
    ];

	/**
	 * Returns the schedule that this day belongs to.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function schedule()
	{
		return $this->belongsTo(Schedule::class, 'schedule_id');
	}

	/**
	 * Returns the week that this day belongs to.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function week()
	{
		return $this->belongsTo(ScheduleWeek::class, 'schedule_week_id');
	}

	/**
	 * Returns the schedule day template that this day is derived from.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function template()
	{
		return $this->belongsTo(ScheduleDayTemplate::class, 'day_template_id');
	}

	/**
	 * Returns the focus allocations for this day.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphMany
	 */
	public function allocations()
	{
		return $this->morphMany(ScheduleAllocation::class, 'reference');
	}
}
