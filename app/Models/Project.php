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

            // Sync with jira
            $model->syncWithJira();

        });
    }

    /**
     * Syncs this model with jira.
     *
     * @return boolean
     */
    public function syncWithJira()
    {
        // Make sure this model has a jira identifier
        if(is_null($this->jira_id ?: $this->jira_key)) {
            return false;
        }

        // Determine the jira component
        $jira = $this->jira();

        // Assign the attributes from jira
        $this->syncAttributesWithJira($jira);

        // Sync the related entities
        // $this->syncLeadWithJira($jira);
        // $this->syncComponentsWithJira($jira);
        // $this->syncIssueTypesWithJira($jira);
        // $this->syncVersionsWithJira($jira);

        // Return success
        return true;
    }

    /**
     * Syncs this attributes with jira.
     *
     * @return boolean
     */
    protected function syncAttributesWithJira(JiraProject $jira)
    {
        $this->jira_id = $jira->id;
        $this->jira_key = $jira->key;
        $this->display_name = $jira->name;
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
