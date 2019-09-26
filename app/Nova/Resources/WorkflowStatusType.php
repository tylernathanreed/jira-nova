<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class WorkflowStatusType extends Resource
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
    public static $model = \App\Models\WorkflowStatusType::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Status Types';
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

            Field::number('Jira ID', 'jira_id')->onlyOnDetail(),

            Field::morphTo('Scope', 'scope')->exceptOnForms(),

            Field::belongsTo('Group', 'group', WorkflowStatusGroup::class)->sortable(),

            Field::text('Name', 'name')->exceptOnForms()->sortable(),

            Field::text('Description', 'description')->exceptOnForms(),

            Field::text('Color', 'color')->exceptOnForms(),

            Field::dateTime('Created At', 'created_at')->onlyOnDetail(),

            Field::dateTime('Updated At', 'updated_at')->onlyOnDetail()

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
