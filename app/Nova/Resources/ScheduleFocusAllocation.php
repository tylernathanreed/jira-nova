<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class ScheduleFocusAllocation extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Scheduling';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduleFocusAllocation::class;

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = true;

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Allocations';
    }

    /**
     * Get the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->schedule->display_name . '; ' . $this->focusGroup->display_name;
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()->onlyOnDetail(),

            Field::belongsTo('Schedule', 'schedule', Schedule::class)->rules('required'),

            Field::belongsTo('Focus Group', 'focusGroup', FocusGroup::class)->rules('required'),

            Field::number('Sunday', 'sunday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Monday', 'monday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Tuesday', 'tuesday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Wednesday', 'wednesday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Thursday', 'thursday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Friday', 'friday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),
            Field::number('Saturday', 'saturday_allocation')->min(0)->max(86400)->step(1)->rules('required', 'min:0', 'max:86400'),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::dateTime('Deleted At', 'deleted_at')
                ->onlyOnDetail(),

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
