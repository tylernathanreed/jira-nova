<?php

namespace App\Support\Jira\Query;

class Grammar
{
    /**
     * The grammar specific operators.
     *
     * @var array
     */
    protected $operators = [];

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'wheres',
        'orders'
    ];

    /**
     * Compile a select query into SQL.
     *
     * @param  \App\Jira\Query\Builder  $query
     *
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        // If the query does not have any columns set, we'll set the columns to the
        // * character to just get all of the columns from the database. Then we
        // can build the query and concatenate all the pieces together as one.
        $original = $query->columns;

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        // To compile the query, we'll spin through each component of the query and
        // see if that component exists. If it does we'll just call the compiler
        // function for the component which is responsible for making the SQL.
        $sql = trim($this->concatenate(
            $this->compileComponents($query))
        );

        $query->columns = $original;

        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     *
     * @return array
     */
    protected function compileComponents(Builder $query)
    {
        $sql = [];

        foreach ($this->selectComponents as $component) {
            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            if (isset($query->$component) && ! is_null($query->$component)) {
                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param  \App\Jira\Query\Builder  $query
     *
     * @return string
     */
    protected function compileWheres(Builder $query)
    {
        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        if (is_null($query->wheres)) {
            return '';
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql = $this->compileWheresToArray($query)) > 0) {
            return $this->concatenateWhereClauses($query, $sql);
        }

        return '';
    }

    /**
     * Get an array of all the where clauses for the query.
     *
     * @param  \App\Jira\Query\Builder  $query
     *
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            return $where['boolean'].' '.$this->{"where{$where['type']}"}($query, $where);
        })->all();
    }

    /**
     * Format the where clause statements into one string.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $sql
     *
     * @return string
     */
    protected function concatenateWhereClauses($query, $sql)
    {
        return $this->removeLeadingBoolean(implode(' ', $sql));
    }

    /**
     * Compile a basic where clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' in ('.$this->parameterize($where['values']).')';
        }

        return 'created = 1970-01-01';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereNotIn(Builder $query, $where)
    {
        if (! empty($where['values'])) {
            return $this->wrap($where['column']).' not in ('.$this->parameterize($where['values']).')';
        }

        return 'created > 1970-01-01';
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is empty';
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereNotNull(Builder $query, $where)
    {
        return $this->wrap($where['column']).' is not empty';
    }

    /**
     * Compile a nested where clause.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array                    $where
     *
     * @return string
     */
    protected function whereNested(Builder $query, $where)
    {
        return '('.$this->compileWheres($where['query']).')';
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array  $orders
     *
     * @return string
     */
    protected function compileOrders(Builder $query, $orders)
    {
        if (! empty($orders)) {
            return 'order by '.implode(', ', $this->compileOrdersToArray($query, $orders));
        }

        return '';
    }

    /**
     * Compile the query orders to an array.
     *
     * @param  \App\Jira\Query\Builder  $query
     * @param  array  $orders
     *
     * @return array
     */
    protected function compileOrdersToArray(Builder $query, $orders)
    {
        return array_map(function ($order) {
            return $order['sql'] ?? $this->wrap($order['column']).' '.$order['direction'];
        }, $orders);
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string  $value
     *
     * @return string
     */
    public function wrap($value)
    {
        return $this->wrapValue($value);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    /**
     * Create query parameter place-holders for an array.
     *
     * @param  array   $values
     *
     * @return string
     */
    public function parameterize(array $values)
    {
        return implode(', ', array_map([$this, 'parameter'], $values));
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed   $value
     *
     * @return string
     */
    public function parameter($value)
    {
        return strpos($value, ' ') !== false
        	? '"'.str_replace('"', '\\"', $value).'"'
        	: $value;
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array   $segments
     *
     * @return string
     */
    protected function concatenate($segments)
    {
        return implode(' ', array_filter($segments, function ($value) {
            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param  string  $value
     *
     * @return string
     */
    protected function removeLeadingBoolean($value)
    {
        return preg_replace('/and |or /i', '', $value, 1);
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d';
    }
}