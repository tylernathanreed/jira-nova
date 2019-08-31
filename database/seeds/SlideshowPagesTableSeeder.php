<?php

use App\Support\Database\Seeds\CsvSeeder;

class SlideshowPagesTableSeeder extends CsvSeeder
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SlideshowPage::class;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [
        'slideshow_id',
        'display_name'
    ];

    /**
     * The select columns to replace with other selections.
     *
     * @var array
     */
    public $replacements = [
        'slideshow_id' => 'slideshows.system_name as slideshow_system_name'
    ];

    /**
     * The inverse select columns to replace with other selections.
     *
     * @var array
     */
    public $inverseReplacements = [
        'slideshow_system_name' => 'slideshows.id as slideshow_id'
    ];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [
        'slideshows.system_name'
    ];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [
        'slideshows.system_name' => 'asc',
        'order' => 'asc'
    ];

    /**
     * The seedable model relations.
     *
     * @var array
     */
    public $joinRelations = [
        'slideshow'
    ];

    /**
     * The inverse seedable model relations.
     *
     * @var array
     */
    public $inverseJoinRelations = [
        'slideshowFromSeed'
    ];
}
