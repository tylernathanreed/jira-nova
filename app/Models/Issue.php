<?php

namespace App\Models;

use JiraRestApi\Issue\Issue as JiraIssue;

class Issue extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jira_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'due_date'
    ];

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
            $this->jira_id = $jira->id;;
            $this->jira_key = $jira->key;
            $this->issue_categories = null;
            $this->summary = $jira->summary;
            $this->description = $jira->description;
            $this->due_date = $jira->duedate;
            $this->time_estimated = $jira->timeoriginalestimate;
            $this->time_spent = $jira->timespent;
            $this->time_remaining = $jira->timeestimate;
            $this->last_viewed_at = optional($jira->lastViewed)->scalar;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

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
        return $this->belongsTo(IssueStatusType::class, 'issue_status_type_id');
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
