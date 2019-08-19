<?php

use App\Support\Database\Seeds\CsvSeeder;

class CachesTableSeeder extends CsvSeeder
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Cache::class;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [
        'model_class'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'model_class'
    ];

    /**
     * The columns to ignore for seeding.
     *
     * @var array
     */
    public $ignore = [
        'status',
        'build_started_at',
        'build_completed_at',
        'build_record_count',
        'build_record_total',
        'update_started_at',
        'update_completed_at',
        'update_record_count',
        'update_record_total',
        'updates_since_build'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'model_class' => 'asc'
    ];
}
