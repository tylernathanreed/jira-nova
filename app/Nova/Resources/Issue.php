<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;

class Issue extends Resource
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
    public static $model = \App\Models\Issue::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'summary';

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'jira_key', 'summary'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::id()->sortable()->onlyOnDetail(),

            Field::belongsTo('Type', 'type', IssueType::class),

            Field::belongsTo('Priority', 'priority', Priority::class),

            Field::text('Key', 'jira_key')->sortable(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Field::text('Summary', 'summary')->onlyOnDetail(),

            Field::belongsTo('Assignee', 'assignee', User::class),

            Field::belongsTo('Status', 'status', IssueStatusType::class),

            // Field::text('Category', 'issue_category')->sortable(),

            Field::belongsTo('Reporter', 'reporter', User::class),

            Field::date('Due', 'due_date')->sortable(),

            Field::text('Original Estimate', 'time_estimated'),

            Field::text('Remaining Estimate', 'time_remaining'),

            Field::text('Jira ID', 'jira_id')->onlyOnDetail(),

            Field::belongsTo('Project', 'project', Project::class)->onlyOnDetail(),

            Field::belongsTo('Parent', 'parent', static::class)->onlyOnDetail(),

            Field::textarea('Description', 'description')->onlyOnDetail()

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
            new \App\Nova\Actions\SyncIssueFromJira
        ];
    }
}
