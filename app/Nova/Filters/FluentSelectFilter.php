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
}
