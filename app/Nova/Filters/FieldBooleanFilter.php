<?php

namespace App\Nova\Filters;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class FieldBooleanFilter extends SelectFilter
{
    /**
     * The column being filtered.
     *
     * @var string
     */
    public $column;

    /**
     * The default value.
     *
     * @var mixed
     */
    public $default;

    /**
     * Creates and returns a new boolean field filter.
     *
     * @param  string       $column
     * @param  string|null  $name
     * @param  mixed        $default
     *
     * @return $this;
     */
    public function __construct($column, $name = null, $default = null)
    {
        $this->column = $column;
        $this->name = $name ?? Str::title($column);
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
        return $query->where($this->column, '=', $value);
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
            [1 => 'Yes', 0 => 'No']
        );
    }

    /**
     * Returns the default value.
     *
     * @return mixed
     */
    public function default()
    {
        return $this->default ?? '';
    }

    /**
     * Sets the default value.
     *
     * @param  mixed  $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }
}
