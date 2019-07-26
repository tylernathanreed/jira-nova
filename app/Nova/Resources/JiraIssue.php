<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class JiraIssue extends Resource
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
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [

            Field::text('key', 'key'),
            Field::text('url', 'url'),
            Field::text('type_name', 'type_name'),
            Field::text('type_icon_url', 'type_icon_url'),
            Field::text('is_subtask', 'is_subtask'),
            Field::text('parent_key', 'parent_key'),
            Field::text('parent_url', 'parent_url'),
            Field::text('status_name', 'status_name'),
            Field::text('status_color', 'status_color'),
            Field::text('summary', 'summary'),
            Field::text('due_date', 'due_date'),
            Field::text('estimate_remaining', 'estimate_remaining'),
            Field::text('estimate_date', 'estimate_date'),
            Field::text('estimate_diff', 'estimate_diff'),
            Field::text('priority_name', 'priority_name'),
            Field::text('priority_icon_url', 'priority_icon_url'),
            Field::text('reporter_name', 'reporter_name'),
            Field::text('reporter_icon_url', 'reporter_icon_url'),
            Field::text('assignee_name', 'assignee_name'),
            Field::text('assignee_icon_url', 'assignee_icon_url'),
            Field::text('issue_category', 'issue_category'),
            Field::text('focus', 'focus'),
            Field::text('epic_key', 'epic_key'),
            Field::text('epic_url', 'epic_url'),
            Field::text('epic_name', 'epic_name'),
            Field::text('epic_color', 'epic_color'),
            Field::text('links', 'links'),
            Field::text('blocks', 'blocks'),
            Field::text('rank', 'rank')

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
        return [
            new \App\Nova\Metrics\DelinquentIssuesByDiff
        ];
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
        return [
            new \App\Nova\Filters\IssueFocus,
            new \App\Nova\Filters\Assignee
        ];
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
        return [
            new \App\Nova\Actions\SaveSwimlaneChanges
        ];
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Support\Collection  $fields
     *
     * @return array
     */
    public function serializeForIndex(NovaRequest $request, $fields = null)
    {
        return collect($fields ?: $this->indexFields($request))->pluck('value', 'attribute')->all();
    }
}
