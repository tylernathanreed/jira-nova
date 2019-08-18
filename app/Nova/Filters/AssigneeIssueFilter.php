<?php

namespace App\Nova\Filters;

use Jira;
use Illuminate\Http\Request;

class AssigneeIssueFilter extends SelectFilter
{
    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name = 'Assignee';

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
        return $query->where('assignee_key', '=', $value);
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
        return array_flip(
            collect(Jira::users()->findAssignableUsers(['project' => 'UAS']))->pluck('displayName', 'key')->all()
        );
    }
}
