<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use App\Models\Epic as EpicModel;

class Issue extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = self::class;

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
    public static $title = 'key';

    /**
     * The additional value that can be used to provide context for the resource when being displayed.
     *
     * @var string
     */
    public static $subtitle = 'summary';

    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = true;


    /**
     * The number of resources to show per page via relationships.
     *
     * @var int
     */
    public static $perPageViaRelationship = 10;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'key', 'summary'
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

            Field::avatar('T')->thumbnail(function() {
                return $this->type_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            // Field::text('Type', 'type_name')->onlyOnDetail(),

            Field::avatar('P')->thumbnail(function() {
                return $this->priority_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            // Field::text('Priority', 'priority_name')->onlyOnDetail(),

            Field::badgeUrl('Key', 'key')->toUsing(function($value, $resource) {
                return [
                    'name' => 'detail',
                    'params' => [
                        'resourceName' => 'issues',
                        'resourceId' => $resource->id,
                    ],
                ];
            })->style([
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '14px',
                'fontWeight' => '400',
            ])->exceptOnForms(),

            Field::badgeUrl('Epic', 'epic_name')->backgroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->epic_color}.background");
            })->foregroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->epic_color}.color");
            })->linkUsing(function($value, $resource) {
                return !is_null($resource->epic_id) ? EpicModel::getInternalUrlForId($resource->epic_id) : $resource->epic_url;
            })->style([
                'borderRadius' => '3px',
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => 'normal',
                'marginTop' => '0.25rem'
            ])->exceptOnForms(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Field::text('Summary', 'summary')->onlyOnDetail(),

            Field::badgeUrl('Status', 'status_name')->backgroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->status_color}.background");
            })->foregroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->status_color}.color");
            })->style([
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => '600',
                'borderRadius' => '3px',
                'textTransform' => 'uppercase',
                'marginTop' => '0.25rem'
            ])->exceptOnForms(),

            // Field::text('status_color', 'status_color'),

            Field::text('Issue Category', 'issue_category')->onlyOnDetail(),
            Field::text('Focus', 'focus')->onlyOnDetail(),

            // Field::text('assignee_key', 'assignee_key'),
            Field::text('Assignee', 'assignee_name')->onlyOnDetail(),

            Field::avatar('A')->thumbnail(function() {
                return $this->assignee_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            // Field::text('reporter_key', 'reporter_key'),
            Field::text('Reporter', 'reporter_name')->onlyOnDetail(),

            Field::avatar('R')->thumbnail(function() {
                return $this->reporter_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::date('Due', 'due_date')->sortable(),

            Field::date('Estimate', 'estimate_date')->sortable()->exceptOnForms(),

            Field::number('Remaining', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->exceptOnForms()->sortable(),

            // Field::text('estimate_diff', 'estimate_diff'),

            Field::text('URL', 'url')->onlyOnDetail(),

            // Field::text('is_subtask', 'is_subtask'),
            Field::text('Parent', 'parent_key')->onlyOnDetail(),
            // Field::text('parent_url', 'parent_url'),

            Field::code('Labels', 'labels')->json()->onlyOnDetail(),

            Field::code('Links', 'links')->json()->onlyOnDetail(),
            // Field::text('blocks', 'blocks'),

            Field::text('Rank', 'rank')->exceptOnForms()->sortable(),

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
            (new \App\Nova\Metrics\IssueCreatedByDateValue)->where('focus', 'Ticket')->setName('Ticket Entry'),
            (new \App\Nova\Metrics\IssueCreatedByDateTrend)->width('2/3'),
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
        return [
            new \App\Nova\Filters\AssigneeIssueFilter,
            new \App\Nova\Filters\StatusIssueFilter
        ];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [
            \App\Nova\Lenses\FilterLens::make($this, 'Backlog')->scope(function($query) { $query->hasLabel('Backlog')->incomplete(); })->addScopedCards([
                new \App\Nova\Metrics\IssueWorkloadByAssigneePartition,
                new \App\Nova\Metrics\IssueCountByAssigneePartition,
                new \App\Nova\Metrics\IssueStatusPartition
            ]),
            \App\Nova\Lenses\FilterLens::make($this, 'Delinquencies')->scope(function($query) { $query->delinquent(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Estimated Delinquencies')->scope(function($query) { $query->willBeDelinquent(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Stale Issues')->scope(function($query) { $query->hasLabel('Stale')->incomplete(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Stretch Items')->scope(function($query) { $query->hasLabel('Stretch')->incomplete(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Tech Debt')->scope(function($query) { $query->hasLabel('Tech-Debt')->incomplete(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Unassigned')->scope(function($query) { $query->unassigned(); }),
            \App\Nova\Lenses\FilterLens::make($this, 'Weekly Commitments')->scope(function($query) { $query->hasLabelLike('Week%')->incomplete(); }),
        ];
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
