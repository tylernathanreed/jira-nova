<?php

namespace App\Models;

class ScheduleDay extends Model
{
	//////////////////
	//* Attributes *//
	//////////////////
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
        // Delete the allocations
        $this->allocations()->delete();
    }

    ///////////////////
    //* Allocations *//
    ///////////////////
    /**
     * Returns the focus allocation for the specified focus type.
     *
     * @param  string  $type
     *
     * @return integer|null
     */
    public function getFocusTypeAllocationAmount($type)
    {
    	return optional($this->getFocusTypeAllocation($type))->focus_allocation;
    }

    /**
     * Sets the focus allocation for the specified focus type to the given amount.
     *
     * @param  string   $type
     * @param  integer  $amount
     *
     * @return \App\Models\ScheduleAllocation
     */
    public function setFocusTypeAllocationAmount($type, $amount)
    {
    	// Find the focus type allocation instance
    	$allocation = $this->getFocusTypeAllocation($type);

    	// If the focus type allocation doesn't exist, create it
    	if(is_null($allocation)) {
    		$allocation = (new ScheduleAllocation)->setAttribute('focus_type', $type);
    	}

    	// Assign the focus allocation
    	$allocation->focus_allocation = $amount;

    	// Save the focus allocation
    	$this->allocations()->save($allocation);

    	// Return the allocation
    	return $allocation;
    }

    /**
     * Returns the focus allocation model for the specified focus type.
     *
     * @param  string  $type
     *
     * @return \App\Models\ScheduleAllocation|null
     */
    public function getFocusTypeAllocation($type)
    {
    	return $this->allocations->where('focus_type', '=', $type)->first();
    }

    /**
     * Returns the specified attribute of this model.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
    	// Check if the attribute key is an allocation mutator
    	if(!is_null($type = $this->getAllocationMutatorType($key))) {

    		// Return the focus type allocation amount
    		return $this->getFocusTypeAllocationAmount($type);

    	}

    	// Return the result of the parent method
    	return parent::getAttribute($key);
    }

    /**
     * Sets the specified attribute of this model to the given value.
     *
     * @param  string  $key
     * @param  mixed   $value
     *
     * @return $this
     */
    public function setAttribute($key, $value)
    {
    	// Check if the attribute key is an allocation mutator
    	if(!is_null($type = $this->getAllocationMutatorType($key))) {

    		// Set the focus type allocation amount
    		$this->setFocusTypeAllocationAmount($type, $value);

    		// Allow chaining
    		return $this;

    	}

    	// Return the result of the parent method
    	return parent::setAttribute($key, $value);
    }

    /**
     * Returns the allocation mutator type of the specified attribute key.
     *
     * @param  string  $key
     *
     * @return string|null
     */
    public function getAllocationMutatorType($key)
    {
    	// Determine the valid focus types
    	$types = [
    		ScheduleAllocation::FOCUS_TYPE_DEV,
    		ScheduleAllocation::FOCUS_TYPE_TICKET,
    		ScheduleAllocation::FOCUS_TYPE_OTHER
    	];

    	// Iterate through each focus type
    	foreach($types as $type) {

	    	// Check if the key matches the mutator convention
    		if($key == "allocations__{$type}") {
    			return $type;
    		}

    	}

    	// The key is not an allocation mutator
    	return null;
    }

    /////////////////////
    //* Relationships *//
    /////////////////////
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
