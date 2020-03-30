<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class SoftwareEnvironmentTier extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Software.Meta';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SoftwareEnvironmentTier::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name'
    ];

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'pipeline_order' => 'asc'
    ];

    /**
     * Returns the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Environment Tiers';
    }

    /**
     * Returns the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Field::id()
                ->onlyOnDetail(),

            Field::number('Pipeline Order', 'pipeline_order')
                ->help('The order in which this environment should appear within the pipeline.')
                ->required()
                ->rules(['numeric', 'min:0', 'max:50000'])
                ->sortable(),

            Field::text('Name', 'name')
                ->help('The displayable name of this tier.')
                ->required()
                ->rules(['max:100'])
                ->sortable(),

            Field::text('Description', 'description')
                ->help('The purpose or service that environments in this tier provide.')
                ->rules(['max:500']),

            Field::hasMany('Environments', 'environments', SoftwareEnvironment::class)

        ];
    }

    /**
     * Returns the cards available for the request.
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
     * Returns the filters available for the resource.
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
     * Returns the lenses available for the resource.
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
     * Returns the actions available for the resource.
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
