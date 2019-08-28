<?php

namespace App\Nova\Lenses;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\LensRequest;

class FilterLens extends Lens
{
    use Concerns\InlineFilterable;

    /**
     * Creates and returns a new lens.
     *
     * @param  \Laravel\Nova\Resource  $resource
     * @param  string                  $name
     *
     * @return static
     */
    public static function make($resource, $name)
    {
        return (new static($resource::newModel()))->setName($name);
    }

    /**
     * Get the query builder / paginator for the lens.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public static function query(LensRequest $request, $query)
    {
        static::applyQueryScope($request, $query);

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
        return $this->resourceIndexFields();
    }

    /**
     * Get the filters available for the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return $this->resourceFilters();
    }

    /**
     * Get the actions available on the lens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return parent::actions($request);
    }
}
