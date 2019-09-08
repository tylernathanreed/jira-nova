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
     * Returns the change log for the specified issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-auditing-record-get
     *
     * @param  array   $options
     *
     * @option  {integer}  "offset"  The page offset (defaults to 0).
     * @option  {integer}  "limit"   The maximum number of items to return per page (defaults to 1000, maximum is 1000).
     * @option  {string}   "filter"  The query string.
     * @option  {string}   "from"    The date and time on or after the audit records must have been created.
     * @option  {string}   "to"      The date and time on or before the audit records must have been created.
     *
     * @return \stdClass
     */
    public function getAuditRecords($options = [])
    {
        return $this->request()->path('auditing/record')->get($options);
    }

    /**
     * Returns the specified issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-issue-issueIdOrKey-get
     *
     * @param  string  $issueIdOrKey
     * @param  array   $options
     *
     * @option  {string|array}  "fields"         A comma-separated list of fields to return for the issue (Default is "*navigable").
     * @option  {boolean}       "fieldsByKeys"   Whether or not fields are referenced by keys rather than ids (Default is false).
     * @option  {string|array}  "expand"         The additional information about the issue to include in the response (Default is null).
     * @option  {string|array}  "properties"     The properties to include for the issue (Default is null).
     * @option  {boolean}       "updateHistory"  Whether or not the issue should be added to the user's viewing history (Default is false).
     *
     * @return \stdClass
     */
    public function getIssue($issueIdOrKey, $options = [])
    {
        // Convert the fields option to a string
        if(isset($options['fields']) && is_array($options['fields'])) {
            $options['fields'] = implode(',', $options['fields']);
        }

        // Convert the expand option to a string
        if(isset($options['expand']) && is_array($options['expand'])) {
            $options['expand'] = implode(',', $options['expand']);
        }

        // Convert the properties option to a string
        if(isset($options['properties']) && is_array($options['properties'])) {
            $options['properties'] = implode(',', $options['properties']);
        }

        // Submit the request
        return $this->request()->path("issue/{$issueIdOrKey}")->get($options);
    }

    /**
     * Returns the change log for the specified issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-issue-issueIdOrKey-changelog-get
     *
     * @param  string  $issueIdOrKey
     * @param  array   $options
     *
     * @option  {integer}  "startAt"     The page offset (defaults to 0).
     * @option  {integer}  "maxResults"  The maximum number of items to return per page (defaults to 100, maximum is 100).
     *
     * @return \stdClass
     */
    public function getChangeLogs($issueIdOrKey, $options = [])
    {
        return $this->request()->path("issue/{$issueIdOrKey}/changelog")->get($options);
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