<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class ScheduleWeek extends Resource
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
    public static $model = \App\Models\ScheduleWeek::class;

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
        return $this->schedule->display_name . ': Week ' . $this->week_number;
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

            Field::number('Week Number', 'week_number')
                ->rules('required')
                ->min(1)
                ->step(1),
                // ->creationRules('unique:schedule_weeks,week_number,NULL,id,schedule_id,{{schedule_id}}')
                // ->updateRules('unique:schedule_weeks,week_number,{{resourceId}},id,schedule_id,{{schedule_id}}'),

            Field::belongsTo('Template', 'template', ScheduleWeekTemplate::class)
                ->rules('required'),

            Field::text('Template System Name', 'week_template_system_name')
                ->onlyOnDetail(),

            Field::date('Start Date', 'start_date'),

            Field::date('Due Date', 'due_date'),

            Field::select('Allocation Type', 'allocation_type')
                ->rules('required')
                ->options([
                    'weekly' => 'Weekly',
                    'daily' => 'Daily'
                ]),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::hasMany('Days', 'days', ScheduleDay::class),

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
