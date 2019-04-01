<?php

namespace App\Models;

use Jira;
use Cache;
use InvalidArgumentException;
use JiraRestApi\Project\Component as JiraComponent;

class Component extends Model
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
     * Creates or updates the specified component from jira.
     *
     * @param  \App\Models\Project  $project
     * @param  array                $attributes
     *
     * @return static
     */
    public static function createOrUpdateFromJira(Project $project, $attributes = [])
    {
        // Determine the jira component
        $jira = static::findJira($attributes);

        // Make sure a jira component was found
        if(is_null($jira)) {
            throw new InvalidArgumentException('Unable to find Jira Component from attributes: ' . json_encode($attributes));
        }

        // Try to find the existing component in our system
        if(!is_null($component = Component::where('jira_id', '=', $jira->id)->first())) {

            // Update the component
            return $component->updateFromJira($project, $jira);

        }

        // Create the component
        return static::createFromJira($project, $jira);
    }

    /**
     * Creates a new component from the specified jira component.
     *
     * @param  \App\Models\Project             $project
     * @param  \JiraRestApi\Project\Component  $jira
     *
     * @return static
     */
    public static function createFromJira(Project $project, JiraComponent $jira)
    {
        // Create a new component
        $component = new static;

        // Update the component from jira
        return $component->updateFromJira($project, $jira);
    }

    /**
     * Syncs this model from jira.
     *
     * @param  \App\Models\Project
     * @param  \JiraRestApi\Project\Component|null
     *
     * @return $this
     */
    public function updateFromJira(Project $project, JiraComponent $jira = null)
    {
        // Perform all actions within a transaction
        return $this->getConnection()->transaction(function() use ($project, $jira) {

            // If a jira component wasn't specified, find it
            $jira = $jira ?: $this->jira();

            // Associate the project
            $this->project()->associate($project);

            // Assign the attributes
            $this->jira_id = $jira->id;
            $this->display_name = $jira->name;
            $this->description = $jira->description;

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /**
     * Finds and returns the specified jira component.
     *
     * @param  array  $attributes
     *
     * @return \JiraRestApi\Project\Component|null
     */
    public static function findJira($attributes = [])
    {
        // Return the result for a set interval
        return static::getJiraCache()->remember(static::class . ':' . json_encode($attributes), 15 * 60, function() use ($attributes) {

            // Determine the project
            if(is_null($project = Project::findJira($attributes))) {
                return null;
            }

            // Make sure a component id was provided
            if(is_null($id = $attributes['component_id'] ?? null)) {
                return null;
            }

            // Return the matching component
            return head(array_filter($project->components, function($component) use ($id) {
                return $component->id == $id;
            }));

        });
    }

    /**
     * Returns the jira component for this component.
     *
     * @return \JiraRestApi\Project\Component
     */
    public function jira()
    {
        return static::findJira([
            'component_id' => $this->jira_id,
            'project_id' => $this->project->jira_id,
            'project_key' => $this->project->jira_key
        ]);
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
