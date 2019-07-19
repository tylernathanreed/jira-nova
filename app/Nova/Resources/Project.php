<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class Project extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Issues';

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
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()->sortable(),

            Field::text('Jira ID', 'jira_id')->rules('required_without:jira_key'),

            Field::text('Jira Key', 'jira_key')->rules('required_without:jira_id'),

            Field::text('Display Name', 'display_name')->exceptOnForms(),

            Field::dateTime('Issues Synched At', 'issues_synched_at')->onlyOnDetail(),

            Field::hasMany('Issue Status Categories', 'issueStatusCategories'),

            Field::hasMany('Issue Status Types', 'issueStatusTypes'),

            Field::hasMany('Issue Fields', 'issueFields'),

            Field::hasMany('Components', 'components'),

            Field::hasMany('Versions', 'versions'),

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
