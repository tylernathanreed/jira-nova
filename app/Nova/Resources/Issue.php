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

            Field::id()->onlyOnDetail(),

            Field::text('Key', 'key'),

            Field::text('URL', 'url')->onlyOnDetail(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Field::text('Summary', 'summary')->onlyOnDetail(),

            Field::text('Priority', 'priority_name')->onlyOnDetail(),
            // Field::text('priority_icon_url', 'priority_icon_url'),

            Field::text('Issue Category', 'issue_category')->onlyOnDetail(),
            Field::text('Focus', 'focus'),

            Field::date('Due', 'due_date'),

            Field::number('Remaining', 'estimate_remaining')->onlyOnDetail(),
            Field::date('Estimate', 'estimate_date'),
            // Field::text('estimate_diff', 'estimate_diff'),

            Field::text('Type', 'type_name')->onlyOnDetail(),
            // Field::text('type_icon_url', 'type_icon_url'),

            // Field::text('is_subtask', 'is_subtask'),
            Field::text('Parent', 'parent_key')->onlyOnDetail(),
            // Field::text('parent_url', 'parent_url'),

            Field::text('Status', 'status_name')->onlyOnDetail(),
            // Field::text('status_color', 'status_color'),

            // Field::text('reporter_key', 'reporter_key'),
            Field::text('Reporter', 'reporter_name')->onlyOnDetail(),
            // Field::text('reporter_icon_url', 'reporter_icon_url'),

            // Field::text('assignee_key', 'assignee_key'),
            Field::text('Assignee', 'assignee_name'),
            // Field::text('assignee_icon_url', 'assignee_icon_url'),

            // Field::text('epic_key', 'epic_key'),
            // Field::text('epic_url', 'epic_url'),
            Field::text('Epic', 'epic_name')->onlyOnDetail(),
            // Field::text('epic_color', 'epic_color'),

            Field::code('labels', 'labels')->json()->onlyOnDetail(),

            Field::code('links', 'links')->json()->onlyOnDetail(),
            // Field::text('blocks', 'blocks'),

            Field::text('Rank', 'rank')->onlyOnDetail(),

            Field::date('Created', 'entry_date')->onlyOnDetail()

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
        return [
            new \App\Nova\Metrics\IssueWorkloadByFocus
        ];
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
            // new \App\Nova\Actions\SyncIssueFromJira
        ];
    }
}
