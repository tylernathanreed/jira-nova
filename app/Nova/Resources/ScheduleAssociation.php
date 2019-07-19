<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class ScheduleAssociation extends Resource
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
    public static $model = \App\Models\ScheduleAssociation::class;

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
    public static $displayInNavigation = false;

    /**
     * Returns the title of this resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->schedule->display_name . ' / ' . $this->weekTemplate->display_name;
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

            Field::belongsTo('Schedule', 'schedule', Schedule::class),

            Field::belongsTo('Template', 'weekTemplate', ScheduleWeekTemplate::class),

            Field::date('Start Date', 'start_date')
                ->rules('nullable', 'date'),

            Field::date('End Date', 'end_date')
                ->rules('nullable', 'date', 'after_or_equal:start_date'),

            Field::number('Hierarchy', 'hierarchy')
                ->min(1)
                ->step(1)
                ->rules('required', 'integer', 'min:1', 'unique:schedule_associations,hierarchy,{{resourceId}},id,schedule_id,{{request.schedule}}')
                ->help('When associations overlap, the highest hierarchy wins.'),

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
