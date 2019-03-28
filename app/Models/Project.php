<?php

namespace App\Models;

use Jira;
use Cache;
use JiraRestApi\Project\Project as JiraProject;

class Project extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'jira_id', 'jira_key'
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        // Call the parent method
        parent::boot();

        // When creating this model...
        static::creating(function($model) {

            // Sync from jira
            $model->updateFromJira();

        });
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \JiraRestApi\User\User|null
     *
     * @return $this
     */
    public function updateFromJira(JiraProject $jira = null)
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira) {

            // If a jira user wasn't specified, find it
            $jira = $jira ?: $this->jira();

            // Assign the attributes from jira
            $this->syncAttributesFromJira($jira);

            // Sync the related entities
            $this->syncLeadFromJira($jira);
            // $this->syncComponentsFromJira($jira);
            // $this->syncIssueTypesFromJira($jira);
            // $this->syncVersionsFromJira($jira);

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /**
     * Syncs this attributes from jira.
     *
     * @param \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncAttributesFromJira(JiraProject $jira)
    {
        $this->jira_id = $jira->id;
        $this->jira_key = $jira->key;
        $this->display_name = $jira->name;
    }

    /**
     * Syncs the project lead from jira.
     *
     * @param \JiraRestApi\Project\Project  $jira
     *
     * @return void
     */
    protected function syncLeadFromJira(JiraProject $jira)
    {
        // Determine the project lead
        $lead = $jira->lead;

        // Determine the account id
        $accountId = $lead['accountId'];

        // Create or update the user from jira
        User::createOrUpdateFromJira(compact('accountId'));
    }

    /**
     * Returns the jira user for this user.
     *
     * @return \JiraRestApi\User\User
     */
    public function jira()
    {
        return static::getJiraCache()->remember(static::class . ':' . $this->id, 15 * 60, function() {
            return Jira::projects()->get($this->jira_id ?: $this->jira_key);
        });
    }

    /**
     * Returns the jira cache.
     *
     * @return \Illuminate\Cache\Repository
     */
    public static function getJiraCache()
    {
        return Cache::store('jira');
    }
}
