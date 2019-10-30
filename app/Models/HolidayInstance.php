<?php

namespace App\Models;

class HolidayInstance extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'holiday_instances';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
        'effective_date',
        'observed_date'
    ];

    ////////////
    //* Boot *//
    ////////////
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        // Call the parent method
        parent::boot();

        // When the model is being saved...
        static::saving(function($model) {

            // And the model is either new or the effective date is being changed...
            if(!$model->exists || $model->isDirty('effective_date')) {

                // Update the observed date
                $model->observed_date = $model->getCalculatedObservedDate();

            }

        });
    }

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the calculated observed date based on the effective date.
     *
     * @return \Carbon\Carbon
     */
    public function getCalculatedObservedDate()
    {
        // Determine the effective date
        $date = $this->effective_date->copy();

        // If the date falls on a Saturday, it is observed the previous day (Friday)
        if($date->isSaturday()) {
            return $date->subDay();
        }

        // If the date falls on a Sunday, it is observed the following day (Monday)
        else if($date->isSunday()) {
            return $date->addDay();
        }

        // Otherwise, the date is observed on its intended day
        return $date;
    }
}
