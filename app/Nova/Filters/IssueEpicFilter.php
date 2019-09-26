<?php

namespace App\Nova\Filters;

use App\Models\Epic;
use Illuminate\Http\Request;

class IssueEpicFilter extends SelectFilter
{
    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Http\Request               $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed                                  $value
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        return $query->where('epic_key', '=', $value);
    }

    /**
     * Get the filter's available options.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function options(Request $request)
    {
        return Epic::where('active', '=', 1)->orderBy('name')->pluck('key', 'name')->all();
    }
}
