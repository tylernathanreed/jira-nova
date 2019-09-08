<?php

namespace App\Nova\Metrics\Concerns;

use Closure;
use ReflectionFunction;
use Laravel\Nova\Http\Requests\NovaRequest;

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
     * Add a basic where in clause as a scope.
     *
     * @param  string|array|\Closure  $column
     * @param  array                  $values
     *
     * @return $this
     */
    public function whereIn($column, $values = null)
    {
        $this->filter(function($query) use ($column, $values) {
            $query->whereIn($column, $values);
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

    /**
     * Returns the appropriate cache key for the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     *
     * @return string
     */
    protected function getCacheKey(NovaRequest $request)
    {
        return parent::getCacheKey() . '.' . $this->getScopeIdentifier();
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return parent::uriKey() . '-' . $this->getScopeIdentifier();
    }

    /**
     * Returns the string identifier for the scope.
     *
     * @return string
     */
    public function getScopeIdentifier()
    {
        // If no filter exists, use a constant
        if(is_null($this->filter)) {
            return 'no-scope';
        }

        // Determine the reflection function
        $reflection = (new ReflectionFunction($this->filter));

        // Return an identifier based on the filter
        return md5($reflection->getFileName() . ':' . $reflection->getEndLine());
    }
}