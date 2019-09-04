<?php

namespace App\Support\Jira\Resource;

use Illuminate\Support\Str;

abstract class Resource
{
    /**
     * The connection name for the resource.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The base url for this resource.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * The connection resolver instance.
     *
     * @var \Reedware\LaravelApi\ConnectionResolverInterface
     */
    protected static $resolver;

    /**
     * Creates and returns a new request builder for this resource.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function newResourceRequest()
    {
        return $this->newBaseQueryBuilder()->endpoint($this->endpoint);
    }

    /**
     * Creates and returns a new request builder instance for the connection.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    protected function newBaseRequestBuilder()
    {
        return $this->getConnection()->request();
    }

    /**
     * Returns the api connection for this resource.
     *
     * @return \Reedware\LaravelApi\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Returns the current connection name for this resource.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Sets the connection associated with this resource.
     *
     * @param  string|null  $name
     *
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolves the specified connection instance.
     *
     * @param  string|null  $connection
     *
     * @return \Reedware\LaravelApi\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Returns the connection resolver instance.
     *
     * @return \Reedware\LaravelApi\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Sets the connection resolver instance.
     *
     * @param  \Reedware\LaravelApi\ConnectionResolverInterface  $resolver
     *
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Returns the endpoint associated with this resource.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Sets the endpoint associated with this resource.
     *
     * @param  string  $endpoint
     *
     * @return $this
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }
}