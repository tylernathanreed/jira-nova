<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class FocusGroup extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Management';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\FocusGroup::class;

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
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'display_order' => 'asc'
    ];

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

            Field::id('ID', 'id')->hideFromIndex(),
            Field::text('Display Name', 'display_name')->rules('required', 'string', 'max:20'),
            Field::number('Display Order', 'display_order')->rules('required', 'min:1', 'max:999')->min(1)->max(999)->step(1)->sortable(),
            Field::text('System Name', 'system_name')->exceptOnForms()->hideFromIndex()->rules('string', 'max:20'),
            Field::text('Description', 'description')->rules('string', 'max:255'),
            Field::swatch('Color', 'color')->exceptOnForms()->rules('required', 'json'),
            Field::number('Priority', 'priority')->exceptOnForms()->rules('required', 'min:1','max:999')->min(1)->max(999)->step(1)->sortable(),
            Field::boolean('Blocking', 'blocks_other_focuses')->exceptOnForms()->help('When checked, issues in this focus are allowed to allocate into all focus groups.'),
            Field::code('Criteria', 'criteria')->json()->rules('required', 'json')->onlyOnDetail(),

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
