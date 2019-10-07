<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class Label extends Resource
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
    public static $model = \App\Models\Label::class;

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
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'name'
    ];

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
            $join->on('aggregates.name', '=', 'labels.name');
        });

        // Include the aggregates
        $query->addSelect('aggregates.estimate_remaining as estimate_remaining');
        $query->addSelect('aggregates.issues_remaining as issues_remaining');

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

            Field::text('Name', 'name')->sortable(),

            Field::number('Remaining Issues', 'issues_remaining')->onlyOnIndex()->sortable(),
            Field::number('Remaining Workload', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->onlyOnIndex()->sortable(),

            Field::number('Total Issues', 'issues_count')->onlyOnIndex()->sortable(),

            Field::belongsToMany('Issues', 'issues', Issue::class)

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

            $filter->whereHas('labels', function($query) use ($request) {
                $query->where('labels.name', '=', $request->resourceId);
            });

            return $filter;

        };

        return [

            // Index metrics
            (new \App\Nova\Metrics\IssueWorkloadPartition)->groupByLabel(),
            (new \App\Nova\Metrics\IssueCountPartition)->groupByLabel(),

            Issue::getIssueCreatedByDateTrend()
                ->label('Issues (for Labels) Created Per Day')
                ->where('labels', '!=', '[]'),

            // Detail metrics
            $scope(Issue::getIssueCreatedByDateValue())->onlyOnDetail(),
            $scope(Issue::getIssueCreatedByDateTrend())->onlyOnDetail(),
            $scope(new \App\Nova\Metrics\IssueStatusPartition)->onlyOnDetail(),
            $scope(Issue::getIssueDeliquenciesByDueDateTrend())->onlyOnDetail(),
            $scope(Issue::getIssueDeliquenciesByEstimatedDateTrend())->onlyOnDetail(),
            $scope((new \App\Nova\Metrics\IssueWorkloadPartition)->groupByAssignee())->onlyOnDetail(),

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
            new \App\Nova\Filters\ExistenceFilter('Has Incomplete Issues', 'issues', function($query) { $query->incomplete(); })
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
