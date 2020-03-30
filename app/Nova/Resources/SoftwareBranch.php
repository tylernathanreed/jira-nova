<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class SoftwareBranch extends Resource
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
    public static $model = \App\Models\SoftwareBranch::class;

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
        'name' => 'asc'
    ];

    /**
     * Returns the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Branches';
    }

    /**
     * Returns the value that should be displayed to represent the resource.
     *
     * @return string
     */
    public function title()
    {
        return $this->application->name . '; ' . $this->name;
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

            Field::belongsTo('Project', 'project', Project::class)
                ->help('The Jira project that manages issues for this branch.')
                ->withoutTrashed()
                ->required(),

            Field::belongsTo('Application', 'application', SoftwareApplication::class)
                ->help('The application this branch provides the source code for.')
                ->withoutTrashed()
                ->showCreateRelationButton()
                ->required(),

            Field::belongsTo('Tier', 'tier', SoftwareBranchTier::class)
                ->help('The sequence of this branch within the pipeline.')
                ->withoutTrashed()
                ->showCreateRelationButton()
                ->required(),

            Field::belongsTo('Target Version', 'targetVersion', Version::class)
                ->help('The intended release version tied to this branch.')
                ->withoutTrashed()
                ->searchable(),

            Field::text('Name', 'name')
                ->help('The name of the branch within version control.')
                ->required()
                ->rules(['max:100'])
                ->sortable()

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
        return [
            new \App\Nova\Lenses\SoftwarePipelineStepsLens($this->newModel()),
            new \App\Nova\Lenses\SoftwarePipelineSummaryLens($this->newModel())
        ];
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
