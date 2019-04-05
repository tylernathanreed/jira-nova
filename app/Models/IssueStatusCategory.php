<?php

namespace App\Models;

class IssueStatusCategory extends Model
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
     * Creates or updates the specified issue status type from jira.
     *
     * @param  \stdclass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira($jira, $options = [])
    {
        // Try to find the existing issue status type in our system
        if(!is_null($statusType = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the issue status type
            return $statusType->updateFromJira($jira, $options);

        }

        // Create the issue status type
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new issue status type from the specified jira issue status type.
     *
     * @param  \stdclass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createFromJira($jira, $options = [])
    {
        // Create a new issue status type
        $statusType = new static;

        // Update the issue status type from jira
        return $statusType->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \stdclass  $jira
     * @param  array      $options
     *
     * @return $this
     */
    public function updateFromJira($jira, $options = [])
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($jira, $options) {

            // If a project was provided, associate it
            if(!is_null($project = ($options['project'] ?? null))) {
                $this->project()->associate($project);
            }

            // Assign the attributes
            $this->jira_id = $jira->id;
            $this->jira_key = $jira->key;
            $this->display_name = $jira->name;
            $this->color_name = $jira->colorName;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /**
     * Returns the project that this status category belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the status types associated to this status category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function types()
    {
        return $this->hasMany(IssueStatusType::class, 'issue_status_category_id');
    }
}
