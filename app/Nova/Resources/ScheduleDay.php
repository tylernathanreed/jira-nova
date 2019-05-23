<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

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
            Field::id()
                ->sortable()
                ->onlyOnDetail(),

            Field::belongsTo('Schedule', 'schedule', Schedule::class)
                ->rules('required'),

            Field::text('Schedule System Name', 'schedule_system_name')
                ->onlyOnDetail(),

            Field::belongsTo('Week', 'week', ScheduleWeek::class)
                ->rules('required'),

            Field::number('Week Number', 'week_number')
                ->rules('required')
                ->min(1)
                ->step(1),

            Field::select('Day in Week', 'day_in_week')
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

            Field::belongsTo('Templates', 'template', ScheduleDayTemplate::class)
                ->rules('required'),

            Field::text('Day Template System Name', 'day_template_system_name')
                ->onlyOnDetail(),

            Field::date('Date', 'date')
                ->rules('required'),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::number('Dev Allocation', 'allocations__dev')
                ->rules('required')
                ->min(0)
                ->step(1),

            Field::number('Ticket Allocation', 'allocations__ticket')
                ->rules('required')
                ->min(0)
                ->step(1),

            Field::number('Other Allocation', 'allocations__other')
                ->rules('required')
                ->min(0)
                ->step(1),

            Field::morphMany('Allocations', 'allocations', ScheduleAllocation::class)

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
