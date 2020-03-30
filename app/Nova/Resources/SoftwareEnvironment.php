<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class SoftwareEnvironment extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Software';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\SoftwareEnvironment::class;

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name', 'url'
    ];

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [
        'name' => 'asc'
    ];

    /**
     * Returns the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Environments';
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

            Field::url('URL', 'url')
                ->help('The host name of this environment.')
                ->required()
                ->sortable(),

            Field::text('Name', 'name')
                ->help('The displayable name of this environment.')
                ->required()
                ->rules(['max:100'])
                ->sortable(),

            Field::belongsTo('Branch', 'branch', SoftwareBranch::class)
                ->help('The code branch the environment is currently using.')
                ->withoutTrashed()
                ->required(),

            Field::belongsTo('Brand', 'brand', SoftwareBrand::class)
                ->help('The specialized branding that this branch supports.')
                ->withoutTrashed()
                ->showCreateRelationButton()
                ->required(),

            Field::belongsTo('Tier', 'tier', SoftwareEnvironmentTier::class)
                ->help('The classification of this environment based on the purpose or service it provides.')
                ->withoutTrashed()
                ->showCreateRelationButton()
                ->required(),

            Field::text('Description', 'description')
                ->help('The purpose or intended use for this environment.')
                ->rules(['max:500'])

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
