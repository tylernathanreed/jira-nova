<?php

namespace App\Nova\Filters;

use Api;
use Jira;
use Illuminate\Http\Request;

class JiraUserFilter extends SelectFilter
{
    /**
     * The displayable name of the filter.
     *
     * @var string
     */
    public $name;

    /**
     * The column to compare against.
     *
     * @var string
     */
    public $key;

    /**
     * Creates and returns a new filter instance.
     *
     * @param  string|null  $name
     * @param  string|null  $key
     *
     * @return $this
     */
    public function __construct($name = null, $key = null)
    {
        $this->name = $name;
        $this->key = $key;
    }

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
        if($value == 'NotNull') {
            return $query->whereNotNull($this->key);
        }

        if($value == 'Null') {
            return $query->whereNull($this->key);
        }

        else {
            return $query->where($this->key, '=', $value);
        }
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
        return array_merge(['(Assigned)' => 'NotNull', '(Unassigned)' => 'Null'], array_flip(
            collect(Api::getUsersAssignableToIssues(['project' => 'UAS']))->pluck('displayName', 'key')->all()
        ));
    }

    /**
     * Use this filter as an assignee filter.
     *
     * @return $this
     */
    public function useAssignee()
    {
        $this->name = 'Assignee';
        $this->key = 'assignee_key';

        return $this;
    }
}
