<?php

namespace App\Nova\Resources;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Fields\BelongsTo;

class Issue extends Resource
{
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
            ID::make()->sortable()->onlyOnDetail(),

            BelongsTo::make('Type', 'type', IssueType::class),

            BelongsTo::make('Priority', 'priority', Priority::class),

            Text::make('Key', 'jira_key')->sortable(),

            Text::make('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Text::make('Summary', 'summary')->onlyOnDetail(),

            BelongsTo::make('Assignee', 'assignee', User::class),

            BelongsTo::make('Status', 'status', IssueStatusType::class),

            // Text::make('Category', 'issue_category')->sortable(),

            BelongsTo::make('Reporter', 'reporter', User::class),

            Date::make('Due', 'due_date')->sortable(),

            Text::make('Original Estimate', 'time_estimated'),

            Text::make('Remaining Estimate', 'time_remaining'),

            Text::make('Jira ID', 'jira_id')->onlyOnDetail(),

            BelongsTo::make('Project', 'project')->onlyOnDetail(),

            BelongsTo::make('Parent', 'parent', static::class)->onlyOnDetail(),

            Textarea::make('Description', 'description')->onlyOnDetail()

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
