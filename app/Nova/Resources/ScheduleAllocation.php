<?php

namespace App\Nova\Resources;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Datetime;

class ScheduleAllocation extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Schedules';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\ScheduleAllocation::class;

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Returns the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->reference_type . ': ' . $this->reference_id . ' (' . $this->focus_type . ')';
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
            ID::make()
                ->sortable()
                ->onlyOnDetail(),

            MorphTo::make('Reference', 'reference')
                ->rules('required')
                ->types([
                    ScheduleWeek::class,
                    ScheduleDay::class
                ]),

            Text::make('Reference System Name', 'reference_system_name')
                ->onlyOnDetail(),

            Select::make('Focus Type', 'focus_type')
                ->rules('required')
                ->options([
                    'dev' => 'Dev',
                    'ticket' => 'Ticket',
                    'other' => 'Other'
                ]),

            Number::make('Focus Allocation', 'focus_allocation')
                ->rules('required')
                ->min(0)
                ->step(1),

            Datetime::make('Created At', 'created_at')
                ->onlyOnDetail(),

            Datetime::make('Updated At', 'updated_at')
                ->onlyOnDetail()

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
