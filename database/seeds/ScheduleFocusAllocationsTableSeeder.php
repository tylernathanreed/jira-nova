<?php

use App\Support\Database\Seeds\CsvSeeder;

class ScheduleFocusAllocationsTableSeeder extends CsvSeeder
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduleFocusAllocation::class;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [
        'schedule.system_name',
        'focusGroup.system_name'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'schedule.system_name',
        'focusGroup.system_name'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'schedule.system_name' => 'asc',
        'focusGroup.system_name' => 'asc'
    ];

    /**
     * The seedable model relations keyed by the local column name.
     *
     * @var array
     */
    public $relations = [
        'schedule_id' => 'schedule',
        'focus_group_id' => 'focusGroup'
    ];
}
