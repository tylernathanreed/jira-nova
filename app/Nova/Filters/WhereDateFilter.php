<?php

namespace App\Nova\Filters;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\DateFilter;

class WhereDateFilter extends DateFilter
{
    /**
     * The column being filtered.
     *
     * @var string
     */
    public $column;

    /**
     * The operator used to compare.
     *
     * @var string
     */
    public $operator;

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
     * @param  string  $operator
     * @param  mixed   $default
     *
     * @return $this;
     */
    public function __construct($name, $column, $operator, $default = null)
    {
        $this->name = $name;
        $this->column = $column;
        $this->operator = $operator;
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
        return $query->where($this->column, $this->operator, $value);
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
}
