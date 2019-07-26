<?php

namespace App\Nova\Filters;

use Laravel\Nova\Resource;
use Illuminate\Container\Container;
use Laravel\Nova\Query\ApplyFilter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Filters\Filter as NovaFilter;

abstract class Filter extends NovaFilter
{
    /**
     * Returns the filters encoded in the specified request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource                   $resource
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getFiltersFromRequest(NovaRequest $request, Resource $resource)
    {
        // Decode the filters
        if(empty($filters = static::decodedFilters($request))) {
            return collect();
        }

        // Determine the available filters
        $availableFilters = static::availableFilters($request, $resource);

        // Map the filters into available filters
        $appliedFilters = collect($filters)->map(function($filter) use ($availableFilters) {

            // Determine the first matching filter
            $matchingFilter = $availableFilters->first(function($availableFilter) use ($filter) {
                return $filter['class'] === $availableFilter->key();
            });

            // If we found a matching filter, convert it to a class / value pair
            if($matchingFilter) {
                return ['filter' => $matchingFilter, 'value' => $filter['value']];
            }

        });

        // Remove empty filters
        $populatedFilters = $appliedFilters->reject(function($filter) {

            // If the value is an array, make sure there's at least one entry
            if(is_array($filter['value'])) {
                return count($filter['value']) < 1;
            }

            // Otherwise, if the value is a string, make sure it's not empty
            else if(is_string($filter['value'])) {
                return trim($filter['value']) === '';
            }

            // Otherwise, make sure the value isn't null
            return is_null($filter['value']);

        });

        // Map the filter pairings into objects
        $applyFilters = $populatedFilters->map(function($filter) {
            return new ApplyFilter($filter['filter'], $filter['value']);
        })->values();

        // Return the filters to be applied
        return $applyFilters;
    }

    /**
     * Returns the decoded filters encoded in the specified request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return array
     */
    protected static function decodedFilters(NovaRequest $request)
    {
        if(empty($request->filters)) {
            return [];
        }

        $filters = json_decode(base64_decode($request->filters), true);

        return is_array($filters) ? $filters : [];
    }

    /**
     * Returns all of the possibly available filters for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Nova\Resource                   $resource
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function availableFilters(NovaRequest $request, Resource $resource)
    {
        return $resource->availableFilters($request);
    }
}
