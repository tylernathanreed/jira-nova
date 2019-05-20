<?php

namespace App\Models;

class ScheduleDayTemplate extends Model
{
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_day_templates';

	/**
	 * Returns the week template that this day template belongs to.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function weekTemplate()
	{
		return $this->belongsTo(ScheduleWeekTemplate::class, 'week_template_id');
	}
}
