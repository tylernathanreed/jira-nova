<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;

class Version extends Resource
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
    public static $model = \App\Models\Version::class;

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
        'issues',
        'releaseNotes'
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
            $join->on('aggregates.id', '=', 'versions.id');
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

            Field::belongsTo('Project', 'project', Project::class),

            Field::text('Name', 'name')->sortable(),

            Field::boolean('Released', 'released')->sortable(),
            Field::date('Release Date', 'release_date')->sortable(),

            Field::number('Remaining Issues', 'issues_remaining')->onlyOnIndex()->sortable(),
            Field::number('Remaining Workload', 'estimate_remaining')->displayUsing(function($value) {
                return number_format($value / 3600, 2);
            })->onlyOnIndex()->sortable(),

            Field::number('Total Issues', 'issues_count')->onlyOnIndex()->sortable(),

            Field::number('Release Notes', 'release_notes_count')->onlyOnIndex()->sortable(),

            Field::belongsToMany('Issues', 'issues', Issue::class),

            Field::belongsToMany('Release Notes', 'releaseNotes', Issue::class)

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

            $filter->whereHas('versions', function($query) use ($request) {
                $query->where('versions.id', '=', $request->resourceId);
            });

            return $filter;

        };

        return [

            // Index metrics
            Version::getPastReleasesTrend(),
            (new \App\Nova\Metrics\IssueCountPartition)->groupByVersion(),
            
            Issue::getIssueCreatedByDateTrend()
                ->label('Issues (for Versions) Created Per Day')
                ->where('fix_versions', '!=', '[]'),

            // Detail metrics
            Version::getReleaseNotesPartition()->where('versions.id', '=', $request->resourceId)->onlyOnDetail(),
            $scope(Issue::getIssueCreatedByDateTrend())->onlyOnDetail(),
            $scope(Issue::getIssueStatusPartition())->onlyOnDetail(),
            $scope(Issue::getIssueCountByTypePartition())->onlyOnDetail(),
            $scope(Issue::getIssueDeliquenciesByEstimatedDateTrend())->onlyOnDetail(),
            $scope(Issue::getIssueCountByPriorityPartition())->onlyOnDetail(),

        ];
    }

    /**
     * Creates and returns a new past releases trend.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getPastReleasesTrend()
    {
        return (new \App\Nova\Metrics\FluentTrend)
            ->model(static::$model)
            ->countOf('id')
            ->label('Release History')
            ->dateColumn('release_date')
            ->suffix('releases');
    }

    /**
     * Creates and returns a new release notes partition.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public static function getReleaseNotesPartition()
    {
        return (new \App\Nova\Metrics\FluentPartition)
            ->model(static::$model)
            ->label('Release Notes')
            ->joinRelation('issues')
            ->useCount()
            ->groupBy("
                case
                    when issues.requires_release_notes = 1
                        then case
                            when issues.release_notes is not null
                                then 'Has Release Notes'
                            else 'Missing Release Notes'
                        end
                    else 'Not Required'
                end
            ")
            ->colors([
                'Has Release Notes' => '#098f56',
                'Missing Release Notes' => '#F5573B',
                'Not Required' => '#5b9bd5'
            ])
            ->help('This metric shows the completion status of release notes.');
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
            new \App\Nova\Filters\FieldBooleanFilter('released', 'Released'),
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
        return [
            new \App\Nova\Lenses\VersionReleaseNotesLens
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
