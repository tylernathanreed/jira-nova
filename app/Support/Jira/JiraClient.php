<?php

namespace App\Support\Jira;

use GuzzleHttp\Client;

class JiraClient
{
    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Creates and returns a new jira client.
     *
     * @param  string  $url
     * @param  array   $config
     *
     * @return $this
     */
    public function __construct($url = '', $config = [])
    {
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * Creates and returns a new client.
     *
     *
     */
    public function newClient()
    {

    }
}