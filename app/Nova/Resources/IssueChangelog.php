<?php

namespace App\Nova\Resources;

use Field;
use Illuminate\Http\Request;
use App\Models\IssueChangelogItem as IssueChangelogItemModel;

class IssueChangelog extends Resource
{
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Meta';

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\IssueChangelog::class;

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
        'issue_key'
    ];

    /**
     * The relationship counts that should be eager loaded when performing an index query.
     *
     * @var array
     */
    public static $withCount = [
        'items'
    ];

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return 'Changelogs';
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

            Field::number('Jira ID', 'jira_id')->exceptOnForms()->sortable(),

            Field::belongsTo('Issue', 'issue', Issue::class)->exceptOnForms()->sortable(),

            Field::avatar('A')->thumbnail(function() {
                return $this->author_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::text('Author', 'author_name')->exceptOnForms()->sortable(),

            Field::date('Created', 'created_at')->exceptOnForms()->sortable(),

            Field::hasMany('Changes', 'items', IssueChangelogItem::class)

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
        return [];
    }

    /**
     * Creates and returns a new estimate extensions value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getEstimateExtensionsValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Estimate Extensions')
            ->select('sum(item_to - item_from) / 3600.0')
            ->precision(2)
            ->dateColumn('created_at')
            ->suffix('hours')
            ->joinRelation('items', function($join) {

                $join->where('item_field_name', '=', IssueChangelogItemModel::FIELD_ORIGINAL_ESTIMATE);

                $join->whereNotNull('item_from');
                $join->where('item_from', '!=', 0);
                $join->whereRaw('cast(item_from as decimal) < cast(item_to as decimal)');

            });
    }

    /**
     * Creates and returns a new estimate reductions value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getEstimateReductionsValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Estimate Reductions')
            ->select('sum(item_from - item_to) / 3600.0')
            ->precision(2)
            ->dateColumn('created_at')
            ->suffix('hours')
            ->joinRelation('items', function($join) {

                $join->where('item_field_name', '=', IssueChangelogItemModel::FIELD_ORIGINAL_ESTIMATE);

                $join->whereNotNull('item_to');
                $join->where('item_to', '!=', 0);
                $join->whereRaw('cast(item_to as decimal) < cast(item_from as decimal)');

            });
    }

    /**
     * Creates and returns a new estimate reductions value metric.
     *
     * @return \Laravel\Nova\Metrics\Metric
     */
    public function getEstimateInflationValue()
    {
        return (new \App\Nova\Metrics\FluentValue)
            ->model(static::$model)
            ->label('Estimate Inflation')
            ->select('1.0 * (max(cast(item_to as decimal)) - min(cast(item_from as decimal))) / min(cast(item_from as decimal))')
            ->useAverageOfAggregates()
            ->groupBy('issues.id')
            ->dateColumn('issues.resolution_date')
            ->joinRelation('issue', function($join) {
                $join->whereNotNull('issues.resolution_date');
            })
            ->joinRelation('items', function($join) {

                $join->where('item_field_name', '=', IssueChangelogItemModel::FIELD_ORIGINAL_ESTIMATE);

                $join->whereNotNull('item_from');
                $join->where('item_from', '!=', 0);

                $join->whereNotNull('item_to');
                $join->where('item_to', '!=', 0);

            })
            ->precision(2)
            ->format([
                'output' => 'percent',
                'mantissa' => 0
            ])
            ->useScalarDelta();
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
            new \App\Nova\Filters\JiraUserFilter('Author', 'author_key')
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
            new \App\Nova\Lenses\StatusTransitionChangelogLens
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
