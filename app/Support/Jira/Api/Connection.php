<?php

namespace App\Support\Jira\Api;

use Reedware\LaravelApi\Connection as ApiConnection;

/**
 * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/
 */
class Connection extends ApiConnection
{
    /**
     * The jira api version.
     *
     * @var string
     */
    protected $version;

    /**
     * The jira api name.
     *
     * @var string
     */
    protected $apiName;

    /**
     * Create a new api connection instance.
     *
     * @param  \GuzzleHttp\Client  $client
     * @param  array               $config
     *
     * @return $this
     */
    public function __construct($client, array $config = [])
    {
        parent::__construct($client, $config);

        $this->version = $config['version'] ?? 'latest';
        $this->apiName = $config['api-name'] ?? 'api';
    }

    /**
     *
     */
    public function getIssue($issueIdOrKey, $fields = '*all')
    {
        return $this->request()->path("issue/{$issueIdOrKey}")->get(['fields' => is_array($fields) ? implode(',', $fields) : $fields]);
    }

    /**
     * Creates and returns a new request builder instance.
     *
     * @return \Reedware\LaravelApi\Request\Builder
     */
    public function request()
    {
        return parent::request()->endpoint("rest/{$this->apiName}/{$this->version}/");
    }

}