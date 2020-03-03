<?php

namespace App\Nova\Filters;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;

class Assignee extends SelectFilter
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
        return $query;
    }

    /**
     * Apply the filter to the given jira options.
     *
     * @param  array  $options
     * @param  mixed  $value
     *
     * @return void
     */
    public function applyToJiraOptions(&$options, $value)
    {
        $options['assignee'] = (array) $value;
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
        return User::pluck('display_name', 'jira_key')->sort()->flip()->toArray();
    }

    /**
     * Returns the default options for the filter.
     *
     * @return array|mixed
     */
    public function default()
    {
        return Auth::user()->jira_key;
    }
}
