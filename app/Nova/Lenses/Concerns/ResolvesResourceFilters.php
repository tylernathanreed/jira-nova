<?php

namespace App\Nova\Lenses\Concerns;

use Nova;
use Illuminate\Http\Request;

trait ResolvesResourceFilters
{
    /**
     * Returns the index filters for the resource.
     *
     * @return array
     */
    public function resourceFilters()
    {
        return Nova::newResourceFromModel($this->resource)->filters(app(Request::class));
    }
}