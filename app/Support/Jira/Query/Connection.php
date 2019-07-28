<?php

namespace App\Support\Jira\Query;

use Closure;
use Exception;
use DateTimeInterface;
use JiraRestApi\JiraException;
use JiraRestApi\Issue\IssueService;

class Connection
{
    /**
     * The maximum size that an internal chunk can be.
     *
     * @var integer
     */
    const CHUNK_LIMIT = 100;

	/**
	 * The issue service instance.
	 *
	 * @var \JiraRestApi\Issue\IssueService
	 */
	protected $service;

    /**
     * The query grammar implementation.
     *
     * @var \App\Support\Jira\Query\Grammar
     */
    protected $grammar;

    /**
     * The query processor implementation.
     *
     * @var \App\Support\Jira\Query\Processor
     */
    protected $processor;

	/**
	 * Creates and returns a new connection instance.
	 *
	 * @param  \JiraRestApi\Issue\IssueService  $service
	 *
	 * @return $this
	 */
	public function __construct($service)
	{
		$this->service = $service;

        $this->useDefaultGrammar();

        $this->useDefaultProcessor();
	}

    /**
     * Set the query grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultGrammar()
    {
        $this->grammar = $this->getDefaultGrammar();
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \App\Support\Jira\Query\Grammar
     */
    protected function getDefaultGrammar()
    {
        return new Grammar;
    }

    /**
     * Set the query processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultProcessor()
    {
        $this->processor = $this->getDefaultProcessor();
    }

    /**
     * Get the default processor instance.
     *
     * @return \App\Support\Jira\Query\Processor
     */
    protected function getDefaultProcessor()
    {
        return new Processor;
    }

    /**
     * Creates and returns a new query builder instance.
     *
     * @return \App\Support\Jira\Query\Builder
     */
    public function newQuery()
    {
        return new Builder(
            $this, $this->getGrammar(), $this->getProcessor()
        );
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string        $query
     * @param  integer       $offset
     * @param  integer|null  $limit
     * @param  array         $columns
     * @param  array         $expand
     * @param  boolean       $validate
     *
     * @return array
     */
    public function select($query, $offset = 0, $limit = null, $columns = ['*'], $expand = [], $validate = false)
    {
    	$parameters = compact('offset', 'limit', 'columns', 'expand', 'validate');

        return $this->run($query, $parameters, function($query, $parameters) {

            // Extract the parameters
        	extract($parameters);

            // Determine the max chunk limit
            $max = static::CHUNK_LIMIT;

            // Determine the first result
            $result = $this->search($query, $offset, min($limit ?? $max, $max), $columns, $expand, $validate);

            // If no limit was provided, grab everything
            $total = $limit ?? $result->total;

            // Initialize the pagination variables
            $page = 1;
            $count = $max;

            // Loop through the pages until we've gathered all of the results
            while($result->maxResults < $total) {

                // If this is the last page, only pull what we need
                if($total - $result->maxResults < $max) {
                    $limit = $total - $result->maxResults;
                }

                // Determine the next result
                $next = $this->search($query, $page * $count + $offset, min($limit ?? $max , $max), $columns, $expand, $validate);

                // Merge the issues into the result
                $result->issues = array_merge($result->issues, $next->issues);

                // Update the max results
                $result->maxResults = count($result->issues);

            }

            // Return the result
            return $result;

        });
    }

    /**
     * Run a JQL statement and log its execution context.
     *
     * @param  string    $query
     * @param  array     $parameters
     * @param  \Closure  $callback
     *
     * @return mixed
     *
     * @throws \JiraRestApi\JiraException
     */
    protected function run($query, $parameters, Closure $callback)
    {
        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $parameters, $callback);
        } catch (JiraException $e) {
            $result = $this->handleQueryException(
                $e, $query, $parameters, $callback
            );
        }

        return $result;
    }

    /**
     * Run a JQL statement.
     *
     * @param  string    $query
     * @param  array     $parameters
     * @param  \Closure  $callback
     *
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function runQueryCallback($query, $parameters, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the JQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query JQL, bindings and time in our memory.
        try {
            $result = $callback($query, $parameters);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with JQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($parameters), $e
            );
        }

        return $result;
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    /**
     * Executes a search using the jira api and returns the results.
     *
     * @param  string        $query
     * @param  integer       $offset
     * @param  integer|null  $limit
     * @param  array         $columns
     * @param  array         $expand
     * @param  boolean       $validate
     *
     * @return \Illuminate\Support\Collection
     */
    public function search($query, $offset = 0, $limit = null, $columns = ['*'], $expand = [], $validate = false)
    {
    	return $this->service->search($query, $offset, $limit, $columns == ['*'] ? [] : $columns, $expand, $validate);
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \App\Support\Jira\Query\Grammar
     */
    public function getGrammar()
    {
        return $this->grammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  \App\Support\Jira\Query\Grammar  $grammar
     *
     * @return $this
     */
    public function setGrammar(Grammar $grammar)
    {
        $this->grammar = $grammar;

        return $this;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \App\Support\Jira\Query
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param  \App\Support\Jira\Query  $processor
     *
     * @return $this
     */
    public function setProcessor(Processor $processor)
    {
        $this->processor = $processor;

        return $this;
    }
}