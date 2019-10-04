<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class Epic extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Management';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Epic::class;

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
     * The relationship counts that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $withCount = [
        'issues'
    ];

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        // Create a new index query
        $query = parent::indexQuery($request, $query);

        // Create a new remaining workload subquery
        $subquery = static::newModel()->newIssueAggregatesQuery();

        // Join into the subquery
        $query->leftJoinSub($subquery, 'aggregates', function($join) {
            $join->on('aggregates.name', '=', 'epics.name');
        });

        // Include the aggregates
        $query->addSelect('aggregates.due_date as due_date');
        $query->addSelect('aggregates.estimate_date as estimate_date');
        $query->addSelect('aggregates.estimate_remaining as estimate_remaining');

        // Return the query
        return $query;
    }

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

            Field::belongsTo('Project', 'project', Project::class)->onlyOnDetail(),

            Field::url('Key', function() {
                return $this->getExternalUrl();
            })->labelUsing(function() {
                return $this->key;
            })->alwaysClickable()->exceptOnForms(),

            Field::badgeUrl('Label', 'name')->backgroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->color}.background");
            })->foregroundUsing(function($value, $resource) {
                return config("jira.colors.{$resource->color}.color");
            })->linkUsing(function($value, $resource) {
                return $resource->getInternalUrl();
            })->style([
                'borderRadius' => '3px',
                'fontFamily' => '\'Segoe UI\'',
                'fontSize' => '12px',
                'fontWeight' => 'normal'
            ])->exceptOnForms(),

            Field::text('Summary', 'summary')->onlyOnDetail(),

            Field::text('Summary', 'summary', function() {
                return strlen($this->summary) > 80 ? substr($this->summary, 0, 80) . '...' : $this->summary;
            })->onlyOnIndex(),

            Field::textarea('Description', 'description'),

            Field::number('Issues', 'issues_count')->onlyOnIndex()->sortable(),

            Field::number('Workload', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->onlyOnIndex()->sortable(),

            Field::date('Due Date', 'due_date')->onlyOnIndex()->sortable()->format('M/D/YY'),

            Field::date('Estimate Date', 'estimate_date')->onlyOnIndex()->sortable()->format('M/D/YY'),

            Field::boolean('Active', 'active')->exceptOnForms(),

            Field::hasMany('Issues', 'issues', Issue::class)

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
        $scope = function($filter) use ($request) {

            $filter->where('epic_id', '=', $request->resourceId);

            return $filter;

        };

        return [

            // Index metrics
            (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByEpic(),
            (new \App\Nova\Metrics\IssueCountPartition)->groupByEpic(),

            Issue::getIssueCreatedByDateTrend()
                ->label('Issues (for Epics) Created Per Day')
                ->whereNotNull('epic_name'),

            // Detail metrics
            $scope(new \App\Nova\Metrics\IssueCreatedByDateValue)->onlyOnDetail(),
            $scope(Issue::getIssueCreatedByDateTrend())->onlyOnDetail(),
            $scope(new \App\Nova\Metrics\IssueStatusPartition)->onlyOnDetail(),
            $scope(new \App\Nova\Metrics\IssueDelinquentByDueDateTrend)->onlyOnDetail(),
            $scope(new \App\Nova\Metrics\IssueDelinquentByEstimatedDateTrend)->onlyOnDetail(),
            $scope(new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee()->onlyOnDetail(),
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
            (new \App\Nova\Filters\FieldBooleanFilter('active'))->setDefault(1)
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
        return [];
    }
}
