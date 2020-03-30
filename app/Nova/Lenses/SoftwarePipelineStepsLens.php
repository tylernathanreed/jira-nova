<?php

namespace App\Nova\Lenses;

use DB;
use Field;
use Illuminate\Http\Request;
use App\Models\Views\SoftwarePipelineStep;
use App\Nova\Resources\SoftwareBranchTier;
use App\Nova\Resources\SoftwareApplication;
use Laravel\Nova\Http\Requests\LensRequest;

class SoftwarePipelineStepsLens extends Lens
{
    /**
     * The displayable name of the lens.
     *
     * @var string
     */
    public $name = 'Pipeline Steps';

    /**
     * Returns the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        // Join into the pipeline steps
        $query->joinRelation('pipelineSteps');

        // Disable table order prefixes
        $request->withoutTableOrderPrefix();

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

            Field::text('Application', 'application_name')->sortable(),

            Field::text('Branch', 'branch_tier_name', function($value, $resource, $attribute) {
                return ($resource->brand_name != 'UAS' && !is_null($resource->brand_name)) ? "{$value} Branded" : $value;
            })->sortable(),

            Field::text('Environment', 'environment_tier_name', function($value, $resource, $attribute) {
                return ($resource->brand_name != 'UAS' && !is_null($resource->brand_name)) ? "{$value} ({$resource->brand_name})" : $value;
            })->sortable(),

            Field::text('Version', 'version_name')->sortable()

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
        return [];
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
