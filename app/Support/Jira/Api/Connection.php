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
     * Returns the list of dashboards owned by or shared with the user.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-dashboard-get
     *
     * @param  array  $options
     *
     * @option  {string}   "filter"      The filter applied to the list of dashboards (valid values are "favourite" and "my").
     * @option  {integer}  "startAt"     The page offset (defaults to 0).
     * @option  {integer}  "maxResults"  The maximum number of items to return per page (defaults to 20, maximum is 1000).
     *
     * @return \stdClass
     */
    public function getDashboards($options = [])
    {
        return $this->request()->path('dashboard')->get($options);
    }

    /**
     * Returns the specified issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-issue-issueIdOrKey-get
     *
     * @param  string  $issueIdOrKey
     * @param  array   $options
     *
     * @option  {string|array}  "fields"         A comma-separated list of fields to return for the issue (defaults to "*navigable").
     * @option  {boolean}       "fieldsByKeys"   Whether or not fields are referenced by keys rather than ids (defaults to false).
     * @option  {string|array}  "expand"         The additional information about the issue to include in the response (defaults to null).
     * @option  {string|array}  "properties"     The properties to include for the issue (defaults to null).
     * @option  {boolean}       "updateHistory"  Whether or not the issue should be added to the user's viewing history (defaults to false).
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
     * Returns the system and custom issue fields.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-field-get
     *
     * @return array
     */
    public function getFields()
    {
        return $this->request()->path('fields')->get();
    }

    /**
     * Returns the list of users matching the given criteria that can be assigned to an issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-user-assignable-search-get
     *
     * @param  array  $options
     *
     * @option  {string}   "query"               A query string that will be matched against user attributes.
     * @option  {string}   "sessionId"           The sessionId of this request.
     * @option  {string}   "accountId"           A query string that is matched against the user accountId.
     * @option  {string}   "project"             The project id or key (case sensistive). Required unless "issueKey" is specified.
     * @option  {string}   "issueKey"            The key of the issue. Required unless "project" is specified.
     * @option  {integer}  "startAt"             The page offset (defaults to 0).
     * @option  {integer}  "maxResults"          The maximum number of items to return per page (defaults to 50, maximum is 1000).
     * @option  {integer}  "actionDescriptorId"  The id of the transition.
     *
     * @return array
     */
    public function getUsersAssignableToIssues($options = [])
    {
        return $this->request()->path('user/assignable/search')->get($options);
    }

    /**
     * Returns all of the issue types.
     *
     * @return array
     */
    public function getIssueTypes()
    {
        return $this->request()->path('issuetype')->get();
    }

    /**
     * Returns all of the worklogs for the specified issue.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-issue-issueIdOrKey-worklog-get
     *
     * @param  string  $issueIdOrKey
     * @param  array   $options
     *
     * @option  {integer}       "startAt"     The page offset (defaults to 0).
     * @option  {integer}       "maxResults"  The maximum number of items to return per page (defaults to 1048576, maximum is 1048576).
     * @option  {string|array}  "expand"      The additional information to include about each worklog.
     *
     * @return array
     */
    public function getIssueWorklogs($issueIdOrKey, $options = [])
    {
        return $this->request()->path("issue/{$issueIdOrKey}/worklog")->get($options);
    }

    /**
     * Returns a paginated list of labels.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-label-get
     *
     * @param  array  $options
     *
     * @option  {integer}  "startAt"     The page offset (defaults to 0).
     * @option  {integer}  "maxResults"  The maximum number of items to return per page (defaults to 1000, maximum is 1000).
     *
     * @return \stdClass
     */
    public function getLabels($options = [])
    {
        return $this->request()->path('label')->get($options);
    }

    /**
     * Returns the issue priorities.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-priority-get
     *
     * @return array
     */
    public function getPriorities()
    {
        return $this->request()->path('priority')->get();
    }

    /**
     * Returns the projects visible to the user.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-project-search-get
     *
     * @param  array  $options
     *
     * @option  {integer}       "startAt"     The page offset (defaults to 0).
     * @option  {integer}       "maxResults"  The maximum number of items to return per page (defaults to 50, maximum is 50).
     * @option  {string}        "orderBy"     The field to order the results by (default is "key").
     * @option  {string}        "query"       The literal string used to filter by the project key or name.
     * @option  {string}        "typeKey"     The project type to order by (valid values are "business", "service_desk", and "software").
     * @option  {integer}       "categoryId"  The project category id to filter by.
     * @option  {string}        "searchBy"    The fields to search against using the query string (default is "key, name").
     * @option  {string}        "action"      The required action name tp filter by (default is "view").
     * @option  {string|array}  "expand"      The additional information to include about each project.
     *
     * @return array
     */
    public function getProjects($options = [])
    {
        // Convert the "expand" option to a string
        if(isset($options['expand']) && is_array($options['expand'])) {
            $options['expand'] = implode(',', $options['expand']);
        }

        // Submit the request and return the result
        return $this->request()->path('project/search')->get($options);
    }

    /**
     * Returns the specified project.
     *
     * @param  string  $projectIdOrKey
     * @param  array   $options
     *
     * @option  {string|array}  "expand"      The additional information to include about the project.
     * @option  {string|array}  "properties"  The project properties to include.
     *
     * @return \stdClass
     */
    public function getProject($projectIdOrKey, $options = [])
    {
        // Convert the "expand" option to a string
        if(isset($options['expand']) && is_array($options['expand'])) {
            $options['expand'] = implode(',', $options['expand']);
        }

        // Convert the "properties" option to a string
        if(isset($options['properties']) && is_array($options['properties'])) {
            $options['properties'] = implode(',', $options['properties']);
        }

        // Submit the request and return the result
        return $this->request()->path("project/{$projectIdOrKey}")->get($options);
    }

    /**
     * Returns the components for the specified project.
     *
     * @param  string  $projectIdOrKey
     *
     * @return array
     */
    public function getProjectComponents($projectIdOrKey)
    {
        return $this->request()->path("project/{$projectIdOrKey}/components")->get();
    }

    /**
     * Returns the paginated components for the specified project.
     *
     * @param  string  $projectIdOrKey
     * @param  array   $options
     *
     * @option  {integer}  "startAt"     The page offset (defaults to 0).
     * @option  {integer}  "maxResults"  The maximum number of items to return per page (defaults to 50, maximum is 50).
     * @option  {string}   "orderBy"     The field to order the results by (default is "name").
     * @option  {string}   "query"       The literal string used to filter by the components by name or description (case insensitive).
     *
     * @return \stdClass
     */
    public function getProjectComponentsPaginated($projectIdOrKey, $options = [])
    {
        return $this->request()->path("project/{$projectIdOrKey}/component", $options)->get();
    }

    /**
     * Returns the valid statuses for a project.
     *
     * @param  string  $projectIdOrKey
     *
     * @return array
     */
    public function getProjectStatuses($projectIdOrKey)
    {
        return $this->request()->path("project/{$projectIdOrKey}/statuses")->get();
    }

    /**
     * Returns the versions for the specified project.
     *
     * @param  string  $projectIdOrKey
     *
     * @return array
     */
    public function getProjectVersions($projectIdOrKey)
    {
        return $this->request()->path("project/{$projectIdOrKey}/versions")->get();
    }

    /**
     * Returns the paginated versions for the specified project.
     *
     * @param  string  $projectIdOrKey
     * @param  array   $options
     *
     * @option  {integer}       "startAt"     The page offset (defaults to 0).
     * @option  {integer}       "maxResults"  The maximum number of items to return per page (defaults to 50, maximum is 50).
     * @option  {string}        "orderBy"     The field to order the results by (default is "name").
     * @option  {string}        "query"       The literal string used to filter by the versions by name or description (case insensitive).
     * @option  {string|array}  "status"      The status value(s) to filter the versions.
     * @option  {string|array}  "expand"      The additional information to include in the response.
     *
     * @return \stdClass
     */
    public function getProjectVersionsPaginated($projectIdOrKey, $options = [])
    {
        // Convert the "status" option to a string
        if(isset($options['status']) && is_array($options['status'])) {
            $options['status'] = implode(',', $options['status']);
        }

        // Convert the "expand" option to a string
        if(isset($options['expand']) && is_array($options['expand'])) {
            $options['expand'] = implode(',', $options['expand']);
        }

        return $this->request()->path("project/{$projectIdOrKey}/version", $options)->get();
    }

    /**
     * Returns the list of all issue resolution values.
     *
     * @return array
     */
    public function getResolutions()
    {
        return $this->request()->path('resolution')->get();
    }

    /**
     * Returns the statuses available in all workflows.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-group-Workflow-statuses
     *
     * @return array
     */
    public function getStatuses()
    {
        return $this->request()->path('status')->get();
    }

    /**
     * Returns a list of all of the status categories.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-statuscategory-get
     *
     * @return array
     */
    public function getStatusCategories()
    {
        return $this->request()->path('statuscategory')->get();
    }

    /**
     * Returns the list of worklogs updated after the given timestamp.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-worklog-updated-get
     *
     * @param  array  $options
     *
     * @option  {mixed}         "since"   The UNIX timestamp after which updated worklogs are returned (defaults to 0).
     * @option  {string|array}  "expand"  The additional information about each worklog to include in the response (defaults to null).
     *
     * @return \stdClass
     */
    public function getUpdatedWorklogs($options = [])
    {
        // Convert the "since" option to a timestamp
        if(isset($options['since']) && !is_int($options['since'])) {
            $options['since'] = carbon($options['since'])->timestamp * 1000;
        }

        // Convert the "expand" option to a string
        if(isset($options['expand']) && is_array($options['expand'])) {
            $options['expand'] = implode(',', $options['expand']);
        }

        // Submit the request and return the result
        return $this->request()->path('worklog/updated')->get($options);
    }

    /**
     * Returns the list of all users (active and inactive).
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-users-search-get
     *
     * @param  array  $options
     *
     * @option  {integer}  "startAt"     The page offset (defaults to 0).
     * @option  {integer}  "maxResults"  The maximum number of items to return per page (defaults to 50, maximum is 50).
     *
     * @return \stdClass
     */
    public function getUsers($options = [])
    {
        return $this->request()->path("users/search")->get($options);
    }

    /**
     * Returns the specified workload.
     *
     * @link https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-rest-api-3-issue-issueIdOrKey-worklog-id-get
     *
     * @param  string  $issueIdOrKey
     * @param  string  $worklogId
     * @param  array   $options
     *
     * @option  {string|array}  "expand"  The additional information to include about the worklog.
     *
     * @return array
     */
    public function getWorklog($issueIdOrKey, $worklogId, $options = [])
    {
        return $this->request()->path("issue/{$issueIdOrKey}/worklog/{$worklogId}")->get($options);
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