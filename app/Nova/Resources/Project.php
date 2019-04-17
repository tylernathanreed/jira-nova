<?php

namespace App\Nova\Resources;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Avatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Datetime;

class Project extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Project::class;

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
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Text::make('Jira ID', 'jira_id')->rules('required_without:jira_key'),

            Text::make('Jira Key', 'jira_key')->rules('required_without:jira_id'),

            Text::make('Display Name', 'display_name')->exceptOnForms(),

            Datetime::make('Issues Synched At', 'issues_synched_at')->onlyOnDetail(),

            HasMany::make('Issue Status Categories', 'issueStatusCategories'),

            HasMany::make('Issue Status Types', 'issueStatusTypes'),

            HasMany::make('Issue Fields', 'issueFields'),

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            (new \App\Nova\Actions\UpdateFromJira)->setOptions([
                'lead' => 'Sync Lead',
                'components' => 'Sync Components',
                'issue_types' => 'Sync Issue Types',
                'versions' => 'Sync Versions',
                'priorities' => 'Sync Priorities',
                'issue_status_types' => 'Sync Issue Status Types',
                'issue_fields' => 'Sync Issue Fields'
            ]),
            new \App\Nova\Actions\SyncIssuesFromProject,
        ];
    }
}
