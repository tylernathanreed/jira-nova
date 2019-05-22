<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class Schedule extends Resource
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
    public static $model = \App\Models\Schedule::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'display_name';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

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

            Field::displayName(),

            Field::text('System Name', 'system_name')
                ->sortable()
                ->rules('nullable', 'string', 'max:50')
                ->creationRules('unique:schedules,system_name')
                ->updateRules('unique:schedules,system_name,{{resourceId}}'),

            Field::belongsTo('Week Templates', 'weekTemplate', ScheduleWeekTemplate::class),

            Field::textarea('Description', 'description')
                ->hideFromIndex()
                ->rules('string', 'max:255'),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::dateTime('Deleted At', 'deleted_at')
                ->onlyOnDetail(),

            Field::hasMany('Weeks', 'weeks', ScheduleWeek::class)

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
