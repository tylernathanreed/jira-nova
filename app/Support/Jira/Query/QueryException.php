<?php

namespace App\Support\Jira\Query;

use RuntimeException;

class QueryException extends RuntimeException
{
    /**
     * The JQL for the query.
     *
     * @var string
     */
    protected $jql;

    /**
     * The parameters for the query.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Create a new query exception instance.
     *
     * @param  string      $jql
     * @param  array       $parameters
     * @param  \Exception  $previous
     *
     * @return $this
     */
    public function __construct($jql, array $parameters, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->jql = $jql;
        $this->parameters = $parameters;
        $this->code = $previous->getCode();
        $this->message = $this->formatMessage($jql, $parameters, $previous);
    }

    /**
     * Format the SQL error message.
     *
     * @param  string      $jql
     * @param  array       $parameters
     * @param  \Exception  $previous
     *
     * @return string
     */
    protected function formatMessage($jql, $parameters, $previous)
    {
        return $previous->getMessage() . ' (JQL: ' . $jql . ')';
    }

    /**
     * Get the JQL for the query.
     *
     * @return string
     */
    public function getJql()
    {
        return $this->jql;
    }

    /**
     * Get the parameters for the query.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
