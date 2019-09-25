<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class WorkflowStatusGroup extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Workflows';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\WorkflowStatusGroup::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'display_name';

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'transition_order' => 'asc'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Status Groups';
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Field::id()->onlyOnDetail(),

            Field::displayName(),

            Field::text('System Name', 'system_name')
                ->sortable()
                ->rules('nullable', 'string', 'max:50')
                ->creationRules('unique:schedules,system_name')
                ->updateRules('unique:schedules,system_name,{{resourceId}}')
                ->hideFromIndex(),

            Field::number('Transition Order', 'transition_order')->min(1)->max(99)->step(1)->rules('required')->sortable(),

            Field::color('Color', 'color')->rules('required'),

            Field::text('Description', 'description')
                ->rules('nullable', 'string', 'max:255')
                ->hideFromIndex(),

            Field::dateTime('Created At', 'created_at')->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')->onlyOnDetail(),

            Field::hasMany('Statuses', 'statuses', WorkflowStatusType::class)

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
