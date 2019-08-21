<?php

namespace App\Nova\Metrics\Concerns;

use Closure;

trait InlineFilterable
{
    /**
     * The query filter for this metric.
     *
     * @var \Closure|null
     */
    public $filter;

    /**
     * Sets the filter for this metric.
     *
     * @param  \Closure  $filter
     *
     * @return $this
     */
    public function filter(Closure $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Applies the filter to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyFilter($query)
    {
        if(!is_null($this->filter)) {
            call_user_func($this->filter, $query);
        }
    }

    /**
     * Add a basic where clause as a filter.
     *
     * @param  string|array|\Closure  $column
     * @param  mixed                  $operator
     * @param  mixed                  $value
     *
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        $this->filter(function($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });

        return $this;
    }

    /**
     * Add a basic where clause as a filter.
     *
     * @param  string         $relation
     * @param  \Closure|null  $callback
     * @param  string         $operator
     * @param  integer        $count
     *
     * @return $this
     */
    public function whereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
    {
        $this->filter(function($query) use ($relation, $callback, $operator, $count) {
            $query->whereHas($relation, $callback, $operator, $count);
        });

        return $this;
    }
}