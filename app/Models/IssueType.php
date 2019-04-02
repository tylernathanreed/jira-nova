<?php

namespace App\Models;

use JiraRestApi\Issue\IssueType as JiraIssueType;

class IssueType extends Model
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
     * Creates or updates the specified issue type from jira.
     *
     * @param  \JiraRestApi\Issue\IssueType  $jira
     * @param  array                         $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira(JiraIssueType $jira, $options = [])
    {
        // Try to find the existing issue type in our system
        if(!is_null($issueType = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the issue type
            return $issueType->updateFromJira($jira, $options);

        }

        // Create the issue type
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new issue type from the specified jira issue type.
     *
     * @param  \JiraRestApi\Issue\IssueType  $jira
     * @param  array                         $options
     *
     * @return static
     */
    public static function createFromJira(JiraIssueType $jira, $options = [])
    {
        // Create a new issue type
        $issueType = new static;

        // Update the issue type from jira
        return $issueType->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\Issue\IssueType  $jira
     * @param  array                         $options
     *
     * @return $this
     */
    public function updateFromJira(JiraIssueType $jira, $options = [])
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira, $options) {

            // Assign the attributes
            $this->jira_id = $jira->id;
            $this->display_name = $jira->name;
            $this->description = $jira->description;
            $this->icon_url = $jira->iconUrl;
            $this->is_subtask = $jira->subtask ?: false;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }
}
