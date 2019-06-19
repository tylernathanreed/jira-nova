<?php

namespace App\Models;

use Jira;
use JiraRestApi\Issue\Issue as JiraIssue;

class Issue extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The field constants.
     *
     * @var string
     */
    const FIELD_ISSUE_TYPE = 'issuetype';
    const FIELD_STATUS = 'status';
    const FIELD_SUMMARY = 'summary';
    const FIELD_DUE_DATE = 'duedate';
    const FIELD_REMAINING_ESTIMATE = 'timeestimate';
    const FIELD_PRIORITY = 'priority';
    const FIELD_REPORTER = 'reporter';
    const FIELD_ASSIGNEE = 'assignee';
    const FIELD_ISSUE_CATEGORY = 'customfield_12005';
    const FIELD_ESTIMATED_COMPLETION_DATE = 'customfield_12011';
    const FIELD_EPIC_KEY = 'customfield_12000';
    const FIELD_RANK = 'customfield_10119';

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jira_id', 'jira_key', 'project_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'due_date'
    ];

    ////////////
    //* Jira *//
    ////////////
    /**
     * Returns an array of raw issues from Jira.
     *
     * @param  array  $option
     *
     * @return array
     */
    public static function getIssuesFromJira($options = [])
    {
        // Determine the search query
        $jql = static::newIssuesFromJiraExpression($options);

        // Initialize the list of issues
        $issues = [];

        // Initialize the pagination variables
        $page = 0;
        $count = 50;

        // Loop until we're out of results
        do {

            // Determine the search results
            $results = Jira::issues()->search($jql, $page * $count, $count, [
                static::FIELD_ISSUE_TYPE,
                static::FIELD_STATUS,
                static::FIELD_SUMMARY,
                static::FIELD_DUE_DATE,
                static::FIELD_REMAINING_ESTIMATE,
                static::FIELD_PRIORITY,
                static::FIELD_REPORTER,
                static::FIELD_ASSIGNEE,
                static::FIELD_ISSUE_CATEGORY,
                static::FIELD_ESTIMATED_COMPLETION_DATE,
                static::FIELD_EPIC_KEY,
                static::FIELD_RANK
            ], [], false);

            // Remap the issues to reference what we need
            $results = array_map(function($issue) {
                return [
                    'key' => $issue->key,
                    'url' => rtrim(config('services.jira.host'), '/') . '/browse/' . $issue->key,
                    'type' => $issue->fields->{static::FIELD_ISSUE_TYPE}->name,
                    'type_icon_url' => $issue->fields->{static::FIELD_ISSUE_TYPE}->iconUrl,
                    'status' => $issue->fields->{static::FIELD_STATUS}->name,
                    'status_color' => $issue->fields->{static::FIELD_STATUS}->statuscategory->colorName,
                    'summary' => $issue->fields->{static::FIELD_SUMMARY},
                    'due_date' => $issue->fields->{static::FIELD_DUE_DATE},
                    'time_estimate' => $issue->fields->{static::FIELD_REMAINING_ESTIMATE},
                    'old_estimated_completion_date' => $issue->fields->{static::FIELD_ESTIMATED_COMPLETION_DATE} ?? null,
                    'priority' => optional($issue->fields->{static::FIELD_PRIORITY})->name,
                    'priority_icon_url' => optional($issue->fields->{static::FIELD_PRIORITY})->iconUrl,
                    'reporter_name' => optional($issue->fields->{static::FIELD_REPORTER})->displayName,
                    'reporter_icon_url' => optional($issue->fields->{static::FIELD_REPORTER})->avatarUrls['16x16'] ?? null,
                    'assignee_name' => optional($issue->fields->{static::FIELD_ASSIGNEE})->displayName,
                    'assignee_icon_url' => optional($issue->fields->{static::FIELD_ASSIGNEE})->avatarUrls['16x16'] ?? null,
                    'issue_category' => optional($issue->fields->{static::FIELD_ISSUE_CATEGORY} ?? null)->value ?? 'Dev',
                    'epic_key' => $issue->fields->{static::FIELD_EPIC_KEY} ?? null,
                    'epic_name' => null,
                    'epic_color' => null,
                    'rank' => $issue->fields->{static::FIELD_RANK}
                ];
            }, $results->issues);

            // Determine the number of results
            $countResults = count($results);

            // If there aren't any results, stop here
            if($countResults == 0) {
                break;
            }

            // Append the results to the list of issues
            $issues = array_merge($issues, $results);

            // Forget the results
            unset($results);

            // Increase the page count
            $page++;

        } while ($countResults == $count);

        // Determine the epic keys
        $epics = array_values(array_unique(array_filter(array_column($issues, 'epic_key'))));

        dd(__FILE__ . ':' . __LINE__, compact('epics'));

        // Return the list of issues
        return $issues;
    }

    /**
     * Returns the jira expression that identifies issues.
     *
     * @param  array  $options
     *
     * @return string
     */
    public static function newIssuesFromJiraExpression($options = [])
    {
        // Determine the applicable focus groups
        $groups = $options['groups'] ?? [
            'dev' => true,
            'ticket' => true,
            'other' => true
        ];

        // Determine the base expression
        $expression = 'assignee in (tyler.reed) AND priority not in (Hold) AND status in (Assigned, "Testing Failed", "Dev Hold", "In Development")';

        // If the "dev" focus group is disabled, exclude them
        if(!$groups['dev']) {
            $expression .= ' AND NOT (("Issue Category" = "Dev" or "Issue Category" is empty) AND priority != Highest)';
        }

        // If the "ticket" focus group is disabled, exclude them
        if(!$groups['ticket']) {
            $expression .= ' AND NOT ("Issue Category" in ("Ticket", "Data") AND priority != Highest)';
        }

        // If the "other" focus group is disabled, exclude them
        if(!$groups['other']) {
            $expression .= ' AND priority != Highest';
        }

        // Add the order by clause
        $expression .= ' ORDER BY Rank ASC';

        // Return the expression
        return $expression;
    }

    /**
     * Creates or updates the specified issue type from jira.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira(JiraIssue $jira, $options = [])
    {
        // Try to find the existing issue type in our system
        if(!is_null($issue = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the issue type
            return $issue->updateFromJira($jira, $options);

        }

        // Create the issue type
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new issue type from the specified jira issue type.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return static
     */
    public static function createFromJira(JiraIssue $jira, $options = [])
    {
        // Create a new issue type
        $issue = new static;

        // Update the issue type from jira
        return $issue->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\Issue\Issue  $jira
     * @param  array                     $options
     *
     * @return $this
     */
    public function updateFromJira(JiraIssue $jira, $options = [])
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira, $options) {

            // If a project was provided, associate it
            if(!is_null($project = ($options['project'] ?? null))) {
                $this->project()->associate($project);
            }

            // If a parent was provided, associate it
            if(!is_null($parent = ($options['parent'] ?? null))) {
                $this->parent()->associate($parent);
            }

            // If an issue type was provided, associate it
            if(!is_null($type = ($options['type'] ?? null))) {
                $this->type()->associate($type);
            }

            // If an issue status was provided, associate it
            if(!is_null($status = ($options['status'] ?? null))) {
                $this->status()->associate($status);
            }

            // If an issue priority was provided, associate it
            if(!is_null($priority = ($options['priority'] ?? null))) {
                $this->priority()->associate($priority);
            }

            // If a reporter was provided, associate it
            if(!is_null($reporter = ($options['reporter'] ?? null))) {
                $this->reporter()->associate($reporter);
            }

            // If an assignee was provided, associate it
            if(!is_null($assignee = ($options['assignee'] ?? null))) {
                $this->assignee()->associate($assignee);
            }

            // If a creator was provided, associate it
            if(!is_null($creator = ($options['creator'] ?? null))) {
                $this->createdBy()->associate($creator);
            }

            // Assign the attributes
            $this->jira_id        = $jira->id;
            $this->jira_key       = $jira->key;
            $this->summary        = $jira->fields->summary;
            $this->description    = $jira->fields->description;
            $this->due_date       = $jira->fields->duedate;
            $this->time_estimated = optional($jira->fields->timeoriginalestimate)->scalar;
            $this->time_spent     = $jira->fields->timespent ?? null;
            $this->time_remaining = $jira->fields->timeestimate ?? null;
            $this->last_viewed_at = optional($jira->fields->lastViewed)->scalar;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /**
     * Returns the jira project for this project.
     *
     * @return \JiraRestApi\Issue\Issue
     */
    public function jira()
    {
        return Jira::issues()->get($this->jira_key);
    }

    /////////////////
    //* Relations *//
    /////////////////
    /** 
     * Returns the project that this issue belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the parent to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Returns the issue type that this issue belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo(IssueType::class, 'issue_type_id');
    }

    /**
     * Returns the status type of this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function status()
    {
        return $this->belongsTo(IssueStatusType::class, 'status_id');
    }

    /**
     * Returns the priority of this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    /**
     * Returns the user that reported this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Returns the user currently assigned to this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Returns the user that created this issue.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
