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

    ////////////
    //* Nova *//
    ////////////
    /**
     * Returns the default schedule data for Nova.
     *
     * @return array
     */
    public static function getDefaultScheduleDataForNova()
    {
        return [
            0 => ['Dev' => 0,             'Ticket' => 0,             'Other' => 0                  ],
            1 => ['Dev' => 4.5 * 60 * 60, 'Ticket' => 0,             'Other' => 3.5 * 60 * 60 * 0.5],
            2 => ['Dev' => 0,             'Ticket' => 5 * 60 * 60,   'Other' => 3 * 60 * 60 * 0.5  ],
            3 => ['Dev' => 5 * 60 * 60,   'Ticket' => 0,             'Other' => 3 * 60 * 60 * 0.5  ],
            4 => ['Dev' => 0,             'Ticket' => 4.5 * 60 * 60, 'Other' => 3.5 * 60 * 60 * 0.5],
            5 => ['Dev' => 5 * 60 * 60,   'Ticket' => 0,             'Other' => 3 * 60 * 60 * 0.5  ],
            6 => ['Dev' => 0,             'Ticket' => 0,             'Other' => 0                  ],
        ];
    }

    /**
     * Returns the Nova data for this schedule.
     *
     * @return array
     */
    public function toNovaData()
    {
        // Determine the days of the week attributes
        $attributes = [
            0 => 'sunday_allocation',
            1 => 'monday_allocation',
            2 => 'tuesday_allocation',
            3 => 'wednesday_allocation',
            4 => 'thursday_allocation',
            5 => 'friday_allocation',
            6 => 'saturday_allocation'
        ];

        // Initialize the data
        $data = array_combine(array_keys($attributes), array_fill(0, count($attributes), []));

        // Determine the allocations
        $allocations = $this->allocations->load('focusGroup');

        // Iterate through each allocation
        foreach($allocations as $allocation) {

            // Iterate through each attribute
            foreach($attributes as $day => $attribute) {

                // Assign the allocation amount into the data
                $data[$day][$allocation->focusGroup->system_name] = $allocation->getAttribute($attribute);

            }

        }

        // Return the data
        return $data;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the allocations associated to this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(ScheduleFocusAllocation::class, 'schedule_id');
    }

    /**
     * Returns the users associated to this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'schedule_id');
    }
}
