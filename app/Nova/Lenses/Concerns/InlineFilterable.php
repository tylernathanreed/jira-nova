<?php

namespace App\Nova\Lenses\Concerns;

use Closure;
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\LensRequest;

trait InlineFilterable
{
    /**
     * The query scope for this lens.
     *
     * @var \Closure|null
     */
    public $scope;

    /**
     * The scopeable cards for this lens.
     *
     * @var array
     */
    public $scopedCards = [];

    /**
     * Sets the scope for this metric.
     *
     * @param  \Closure  $scope
     *
     * @return $this
     */
    public function scope(Closure $scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Applies the scope to the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return void
     */
    public function applyScope($query)
    {
        if(!is_null($this->scope)) {
            call_user_func($this->scope, $query);
        }
    }

    /**
     * Applies the query scope to the lens specified within the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\LensRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return void
     */
    public static function applyQueryScope(LensRequest $request, $query)
    {
        call_user_func($request->lens()->scope, $query);
    }

    /**
     * Add a basic where clause as a scope.
     *
     * @param  string|array|\Closure  $column
     * @param  mixed                  $operator
     * @param  mixed                  $value
     *
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        $this->scope(function($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });

        return $this;
    }

    /**
     * Add a basic where clause as a scope.
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
        $this->scope(function($query) use ($relation, $callback, $operator, $count) {
            $query->whereHas($relation, $callback, $operator, $count);
        });

        return $this;
    }

    /**
     * Adds the specified scoped cards to this lens.
     *
     * @param  array  $cards
     *
     * @return $this
     */
    public function addScopedCards($cards)
    {
        $this->scopedCards = $cards;

        return $this;
    }

    /**
     * Returns the scoped cards (with their scopes applied).
     *
     * @return array
     */
    public function getScopedCards()
    {
        return array_map(function($card) {
            return $card->filter($this->scope);
        }, $this->scopedCards);
    }

    /**
     * Get the cards available on the entity.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array
     */
    public function cards(Request $request)
    {
        return $this->getScopedCards();
    }
}