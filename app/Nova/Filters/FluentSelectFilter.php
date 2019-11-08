<?php

namespace App\Nova\Filters;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class FluentSelectFilter extends SelectFilter
{
    /**
     * The column being filtered.
     *
     * @var string
     */
    public $column;

    /**
     * The options that can be selected.
     *
     * @var array
     */
    public $options;

    /**
     * The default value.
     *
     * @var mixed
     */
    public $default;

    /**
     * The relation being filtered.
     *
     * @var string|null
     */
    public $relation;

    /**
     * Creates and returns a new boolean field filter.
     *
     * @param  string  $name
     * @param  string  $column
     * @param  array   $options
     * @param  mixed   $default
     *
     * @return $this;
     */
    public function __construct($name, $column, $options = [], $default = null)
    {
        $this->name = $name;
        $this->column = $column;
        $this->options = $options;
        $this->default = $default;
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
        // Check for a relation
        if(!is_null($this->relation)) {

            // Wrap the condition in a "where has" clause
            return $query->whereHas($this->relation, function($query) use ($value) {
                $query->where($this->column, '=', $value);
            });

        }

        // Apply the condition to the base query
        return $query->where($this->column, '=', $value);
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key()
    {
        return Str::slug($this->name());
    }

    /**
     * Returns the options that can be selected.
     *
     * @return array
     */
    public function options(Request $request)
    {
        return $this->options;
    }

    /**
     * Set the default options for the filter.
     *
     * @return array|mixed
     */
    public function default()
    {
        return $this->default;
    }

    /**
     * Sets the relation for the filter.
     *
     * @param  string  $relation
     *
     * @return $this
     */
    public function relation($relation)
    {
        $this->relation = $relation;

        return $this;
    }
}
