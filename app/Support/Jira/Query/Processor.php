<?php

namespace App\Support\Jira\Query;

use Closure;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueSearchResult;

class Processor
{
    /**
     * The post processor.
     *
     * @var \Closure|null
     */
    protected static $postProcessor;

    /**
     * The field processor.
     *
     * @var \Closure|null
     */
    protected static $fieldProcessor;

    /**
     * Process the results of a "select" query.
     *
     * @param  \App\Support\Jira\Query\Builder       $query
     * @param  \JiraRestApi\Issue\IssueSearchResult  $results
     *
     * @return \stdClass
     */
    public function processSelect(Builder $query, IssueSearchResult $results)
    {
        return (object) [
            'offset' => $results->startAt,
            'limit' => $results->maxResults,
            'count' => $results->total,
            'issues' => collect($this->processIssues($query, $results->issues))
        ];
    }

    /**
     * Processes the specified jira issues.
     *
     * @param  \App\Support\Jira\Query\Builder  $query
     * @param  array                            $issues
     *
     * @return array
     */
    public function processIssues(Builder $query, $issues)
    {
        // Process each issue
        $issues = array_map(function($issue) use ($query) {
            return $this->processIssue($query, $issue);
        }, $issues);

        // If a post processor exists, use it
        if(!is_null($processor = static::$postProcessor)) {
            $issues = $processor($issues);
        }

        // Return the issues
        return $issues;
    }

    /**
     * Processes the specified jira issue.
     *
     * @param  \App\Support\Jira\Query\Builder  $query
     * @param  \JiraRestApi\Issue\Issue         $issue
     *
     * @return \stdClass
     */
    public function processIssue(Builder $query, Issue $issue)
    {
        // Determine the issue fields
        $fields = (array) $issue->fields;

        // Remove the nested custom fields
        unset($fields['customFields']);

        // Process the fields
        $fields = $this->processFields($fields, $issue);

        // Return the issue
        return (object) array_merge(['key' => $issue->key], $fields);
    }

    /**
     * Processes the specified issue fields.
     *
     * @param  array                     $fields
     * @param  \JiraRestApi\Issue\Issue  $issue
     *
     * @return array
     */
    public function processFields($fields, Issue $issue)
    {
        // If a custom field processor exists, use it
        if(!is_null($processor = static::$fieldProcessor)) {
            return $processor($fields, $issue);
        }

        // Otherwise, return the fields as-is
        return $fields;
    }

    /**
     * Sets the field processor.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public static function map(Closure $callback)
    {
        static::$fieldProcessor = $callback;
    }

    /**
     * Sets the post processor.
     *
     * @param  \Closure  $callback
     *
     * @return void
     */
    public static function post(Closure $callback)
    {
        static::$postProcessor = $callback;
    }
}
