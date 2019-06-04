<?php

namespace App\Nova\Resources;

use Field;
use Carbon\Carbon;
use Laravel\Nova\Panel;
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

            new Panel('Allocations', $this->getWeeklyAllocationFields()),

            new Panel('Daily Allocations', $this->getDailyAllocationFields()),

            Field::hasMany('Days', 'days', ScheduleDay::class),

            Field::morphMany('Allocations', 'allocations', ScheduleAllocation::class)

        ];
    }

    /**
     * Returns the weekly allocation fields.
     *
     * @return array
     */
    protected function getWeeklyAllocationFields()
    {
        return [

            Field::number('Dev Allocation', 'allocations__dev')
                ->rules('required_if:allocation_type,weekly')
                ->min(0)
                ->step(1)
                ->valueToggle(function($toggle) {
                    return $toggle->where('allocation_type', '=', 'weekly');
                }),

            Field::number('Ticket Allocation', 'allocations__ticket')
                ->rules('required_if:allocation_type,weekly')
                ->min(0)
                ->step(1)
                ->valueToggle(function($toggle) {
                    return $toggle->where('allocation_type', '=', 'weekly');
                }),

            Field::number('Other Allocation', 'allocations__other')
                ->rules('required_if:allocation_type,weekly')
                ->min(0)
                ->step(1)
                ->valueToggle(function($toggle) {
                    return $toggle->where('allocation_type', '=', 'weekly');
                })

        ];
    }

    /**
     * Returns the daily allocation fields.
     *
     * @return array
     */
    protected function getDailyAllocationFields()
    {
        // Initialize the list of fields
        $fields = [];

        // Initialize the list of days
        $days = Carbon::getDays();

        // Add the alloctions for each day
        foreach($days as $index => $day) {

            // Add the fields
            $fields = array_merge($fields, [

                Field::allocation("{$day} Dev Allocation", "allocations__{$index}__dev")
                    ->rules('required_if:allocation_type,daily')
                    ->valueToggle(function($toggle) {
                        return $toggle->where('allocation_type', '=', 'daily');
                    }),

                Field::allocation("{$day} Ticket Allocation", "allocations__{$index}__ticket")
                    ->rules('required_if:allocation_type,daily')
                    ->valueToggle(function($toggle) {
                        return $toggle->where('allocation_type', '=', 'daily');
                    }),

                Field::allocation("{$day} Other Allocation", "allocations__{$index}__other")
                    ->rules('required_if:allocation_type,daily')
                    ->valueToggle(function($toggle) {
                        return $toggle->where('allocation_type', '=', 'daily');
                    })

            ]);

        }

        // Return the list of fields
        return $fields;
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
