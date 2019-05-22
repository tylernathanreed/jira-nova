<?php

namespace App\Nova\Resources;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Integer;
use Laravel\Nova\Fields\Datetime;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\MorphMany;

class ScheduleDay extends Resource
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
    public static $model = \App\Models\ScheduleDay::class;

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
        return $this->schedule->display_name . ': Week ' . $this->week_number . ', Day ' . $this->day_in_week;
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

            BelongsTo::make('Schedule', 'schedule', Schedule::class)
                ->rules('required'),

            Text::make('Schedule System Name', 'schedule_system_name')
                ->onlyOnDetail(),

            BelongsTo::make('Week', 'week', ScheduleWeek::class)
                ->rules('required'),

            Number::make('Week Number', 'week_number')
                ->rules('required')
                ->min(1)
                ->step(1),

            Select::make('Day in Week', 'day_in_week')
                ->rules('required')
                ->options([
                    0 => 'Sunday',
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                ]),
                // ->creationRules('unique:schedule_days,day_in_week,NULL,id,schedule_id,{{schedule_id}},week_number,{{week_number}}')
                // ->updateRules('unique:schedule_days,day_in_week,{{resourceId}},id,schedule_id,{{schedule_id}},week_number,{{week_number}}'),

            BelongsTo::make('Templates', 'template', ScheduleDayTemplate::class)
                ->rules('required'),

            Text::make('Day Template System Name', 'day_template_system_name')
                ->onlyOnDetail(),

            Date::make('Date', 'date')
                ->rules('required'),

            Datetime::make('Created At', 'created_at')
                ->onlyOnDetail(),

            Datetime::make('Updated At', 'updated_at')
                ->onlyOnDetail(),

            MorphMany::make('Allocations', 'allocations', ScheduleAllocation::class)

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
