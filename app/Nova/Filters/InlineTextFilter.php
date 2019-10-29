<?php

namespace App\Nova\Filters;

use Closure;
use Illuminate\Http\Request;
use Reedware\NovaTextFilter\TextFilter;

class InlineTextFilter extends TextFilter
{
    use Concerns\Nameable;

    /**
     * The callback to apply the filter.
     *
     * @var \Closure
     */
    public $handler;

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
    	// Determine the handler
    	$handler = $this->handler;

    	// Call the handler
    	$handler($query, $value);
    }

    /**
     * Set the callback to apply the filter.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function handle(Closure $callback)
    {
    	$this->handler = $callback;

    	return $this;
    }

    /**
     * Get the key for the filter.
     *
     * @return string
     */
    public function key()
    {
        return get_class($this) . ':' . $this->name;
    }
}
