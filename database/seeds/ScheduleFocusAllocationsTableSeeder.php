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
        'schedule_id',
        'focus_group_id'
    ];

    /**
     * The select columns to replace with other selections.
     *
     * @var array
     */
    public $replacements = [
        'schedule_id' => 'schedules.system_name as schedule_system_name',
        'focus_group_id' => 'focus_groups.system_name as focus_group_system_name'
    ];

    /**
     * The inverse select columns to replace with other selections.
     *
     * @var array
     */
    public $inverseReplacements = [
        'schedule_system_name' => 'focus_groups.id as focus_group_id',
        'focus_group_system_name' => 'schedules.id as schedule_id'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'schedules.system_name',
        'focus_groups.system_name'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'schedules.system_name' => 'asc',
        'focus_groups.system_name' => 'asc'
    ];

    /**
     * The seedable model relations.
     *
     * @var array
     */
    public $joinRelations = [
        'schedule',
        'focusGroup'
    ];

    /**
     * The inverse seedable model relations.
     *
     * @var array
     */
    public $inverseJoinRelations = [
        'scheduleFromSeed',
        'focusGroupFromSeed'
    ];
}
