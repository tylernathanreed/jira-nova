<?php

namespace App\Nova\Filters;

use CLosure;
use Illuminate\Http\Request;

class ExistenceFilter extends SelectFilter
{
    /**
     * The relation being checked.
     *
     * @var string
     */
    public $relation;

    /**
     * The callback to apply additional quantifiers.
     *
     * @var \Closure|null
     */
    public $callback;

    /**
     * Creates and returns a new existence filter.
     *
     * @param  string         $name
     * @param  string         $relation
     * @param  \Closure|null  $callback
     *
     * @return $this
     */
    public function __construct($name, $relation, Closure $callback = null)
    {
        $this->name = $name;
        $this->relation = $relation;
        $this->callback = $callback;
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
        return $query->whereHas($this->relation, $this->callback, $value ? '>=' : '<', 1);
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
        return array_flip([
            1 => 'Yes',
            0 => 'No'
        ]);
    }
}
