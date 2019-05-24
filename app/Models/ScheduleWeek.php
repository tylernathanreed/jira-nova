<?php

namespace App\Models;

class ScheduleWeek extends Model
{
	//////////////////
	//* Attributes *//
	//////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_weeks';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
    	'start_date',
    	'due_date'
    ];

    ////////////
    //* Boot *//
    ////////////
    /**
     * The boot method for this model.
     *
     * @return void
     */
    protected static function boot()
    {
        // Call the parent method
        parent::boot();

        // When this model is being deleted...
        static::deleting(function($model) {

            // Cascade the deletion to its dependants
            $model->deleteDependants();

        });
    }

    /**
     * Deletes the related models that are dependant upon this model to exist.
     *
     * @return void
     */
    public function deleteDependants()
    {
    	// Delete the days
    	$this->days()->delete();

        // Delete the allocations
        $this->allocations()->delete();
    }

    /////////////////
    //* Relations *//
    /////////////////
	/**
	 * Returns the schedule that this week belongs to.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function schedule()
	{
		return $this->belongsTo(Schedule::class, 'schedule_id');
	}

	/**
	 * Returns the schedule week template that this week is derived from.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function template()
	{
		return $this->belongsTo(ScheduleWeekTemplate::class, 'week_template_id');
	}

	/**
	 * Returns the days that belong to this week.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function days()
	{
		return $this->hasMany(ScheduleDay::class, 'schedule_week_id');
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
