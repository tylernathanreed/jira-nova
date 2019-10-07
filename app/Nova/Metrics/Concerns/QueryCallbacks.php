<?php

namespace App\Nova\Metrics\Concerns;

use Closure;

trait QueryCallbacks
{
    /**
     * The query callbacks for this metric.
     *
     * @var array
     */
    public $queryCallbacks = [];

    /**
     * Adds the specified closure as a query callback.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function scope(Closure $callback)
    {
        $this->queryCallbacks[] = $callback;

        return $this;
    }

    /**
     * Applies the query callbacks to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyQueryCallbacks($query)
    {
        foreach(array_merge($this->queryCallbacks, $this->getDefaultCallbacks()) as $callback) {
            $callback($query);
        }
    }

    /**
     * Returns the default query callbacks.
     *
     * @return array
     */
    public function getDefaultCallbacks()
    {
        return [];
    }

    /**
     * Handles dynamic method calls into this metric.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return $this
     */
    public function __call($method, $parameters = [])
    {
        // Create a query callback based on the method call
        $callback = function($query) use ($method, $parameters) {
            $query->{$method}(...$parameters);
        };

        // Add the query callback
        $this->queryCallbacks[] = $callback;

        // Allow chaining
        return $this;
    }
}