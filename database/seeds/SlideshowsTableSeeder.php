<?php

use App\Support\Database\Seeds\CsvSeeder;

class SlideshowsTableSeeder extends CsvSeeder
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Slideshow::class;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [
        'system_name'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'system_name'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'system_name' => 'asc'
    ];
}
