<?php

namespace App\Models;

class ScheduleAllocation extends Model
{
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'schedule_allocations';

	/**
	 * Returns the reference that this allocates to.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function reference()
	{
		return $this->morphTo('reference');
	}
}
