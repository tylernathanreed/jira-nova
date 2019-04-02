<?php

namespace App\Models;

class Version extends Model
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
        'start_date',
        'release_date'
    ];

    /**
     * Creates or updates the specified version from jira.
     *
     * @param  \stdClass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira($jira, $options = [])
    {
        // Try to find the existing version in our system
        if(!is_null($version = static::where('jira_id', '=', $jira->id)->first())) {

            // Update the version
            return $version->updateFromJira($jira, $options);

        }

        // Create the version
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new version from the specified jira version.
     *
     * @param  \stdClass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createFromJira($jira, $options = [])
    {
        // Create a new version
        $version = new static;

        // Update the version from jira
        return $version->updateFromJira($jira, $options);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \stdClass  $jira
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
            $this->display_name = $jira->name;
            $this->start_date = $jira->startDate ?? null;
            $this->release_date = $jira->releaseDate ?? null;
            $this->archived = $jira->archived ?? false;
            $this->released = $jira->released ?? false;
            $this->overdue = $jira->overdue ?? false;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /**
     * Returns the project that this component belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
