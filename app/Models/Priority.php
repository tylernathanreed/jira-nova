<?php

namespace App\Models;

use JiraRestApi\Issue\Priority as JiraPriority;

class Priority extends Model
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
     * Creates or updates the specified priority from jira.
     *
     * @param  \JiraRestApi\Issue\Priority  $jira
     * @param  array                        $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira(JiraPriority $jira, $options = [])
    {
        // Try to find the existing priority in our system
        if(!is_null($priority = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the priority
            return $priority->updateFromJira($jira, $options);

        }

        // Create the priority
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new priority from the specified jira priority.
     *
     * @param  \JiraRestApi\Issue\Priority  $jira
     * @param  array                        $options
     *
     * @return static
     */
    public static function createFromJira($jira, $options = [])
    {
        // Create a new priority
        $priority = new static;

        // Update the priority from jira
        return $priority->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\Issue\Priority  $jira
     * @param  array                        $options
     *
     * @return $this
     */
    public function updateFromJira($jira, $options = [])
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira, $options) {

            // Assign the attributes
            $this->jira_id = $jira->id;
            $this->display_name = $jira->name;
            $this->icon_url = $jira->iconUrl;
            $this->status_color = $jira->statusColor;
            $this->description = $jira->description;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }
}
