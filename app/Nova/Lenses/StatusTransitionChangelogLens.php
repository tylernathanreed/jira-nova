<?php

namespace App\Nova\Lenses;

use Field;
use Illuminate\Http\Request;
use App\Nova\Resources\Issue;
use App\Models\IssueChangelogItem;
use Laravel\Nova\Http\Requests\LensRequest;

class StatusTransitionChangelogLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Status Transitions';

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        // Join into changelog items
        $query->joinRelation('items', function($join) {

            // Filter to status transitions
            $join->where('item_field_name', '=', IssueChangelogItem::FIELD_STATUS);

        });

        // Return the query
        return $request->withOrdering($request->withFilters(
            $query
        ));
    }

    /**
     * Get the fields available to the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            Field::number('Jira ID', 'jira_id')->sortable(),

            Field::belongsTo('Issue', 'issue', Issue::class)->sortable(),

            Field::avatar('A')->thumbnail(function() {
                return $this->author_icon_url;
            })->maxWidth(16)->onlyOnIndex(),

            Field::dateTime('Created', 'created_at')->sortable(),

            Field::text('From', 'item_from'),

            Field::text('To', 'item_to')

        ];

    }

    /**
     * Returns the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Returns the cards available on the entity.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return [
            \App\Nova\Dashboards\GroomingDashboard::getKickbacksTrendMetric(),
            \App\Nova\Dashboards\DevelopmentDashboard::getKickbacksTrendMetric(),
            \App\Nova\Dashboards\TestingDashboard::getKickbacksTrendMetric()
        ];
    }

    /**
     * Returns the actions available on the lens.
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
