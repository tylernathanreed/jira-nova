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
    public static $group = 'Scheduling';

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

            Field::select('Type', 'type')->options([
                'Simple' => 'Simple'
            ])->rules('required'),

            Field::number('Weekly Allocation', 'simple_weekly_allocation')
                ->rules('required_if:type,Simple')
                ->min(0)
                ->max(40)
                ->step(0.5)
                ->help('Measured in hours.')
                ->onlyOnForms()
                ->valueToggle(function($query) {
                    $query->where('type', '=', 'Simple');
                }),

            Field::textarea('Description', 'description')
                ->hideFromIndex()
                ->rules('nullable', 'string', 'max:255'),

            Field::dateTime('Created At', 'created_at')
                ->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')
                ->onlyOnDetail(),

            Field::dateTime('Deleted At', 'deleted_at')
                ->onlyOnDetail(),

            Field::number('Weekly Allocation', function() {
                return $this->getWeeklyAllocationTotal() / 3600 . ' Hours';
            }),

            Field::hasMany('Allocations', 'allocations', ScheduleFocusAllocation::class)

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
