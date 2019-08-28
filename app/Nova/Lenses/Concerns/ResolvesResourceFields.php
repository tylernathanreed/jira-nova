<?php

namespace App\Nova\Lenses\Concerns;

use Nova;
use Laravel\Nova\Http\Requests\NovaRequest;

trait ResolvesResourceFields
{
    /**
     * Returns the index fields for the resource.
     *
     * @return array
     */
    public function resourceIndexFields()
    {
        return Nova::newResourceFromModel($this->resource)->indexFields(app(NovaRequest::class))->all();
    }
}