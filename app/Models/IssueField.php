<?php

namespace App\Models;

class IssueField extends Model
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'operations' => 'array',
        'allowed_values' => 'array'
    ];

    /**
     * Creates or updates the specified issue type from jira.
     *
     * @param  \stdclass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createOrUpdateFromJira($jira, $options = [])
    {
        // Try to find the existing issue type in our system
        if(!is_null($issueType = static::where('jira_key', '=', $jira->key)->first())) {

            // Update the issue type
            return $issueType->updateFromJira($jira, $options);

        }

        // Create the issue type
        return static::createFromJira($jira, $options);
    }

    /**
     * Creates a new issue type from the specified jira issue type.
     *
     * @param  \stdclass  $jira
     * @param  array      $options
     *
     * @return static
     */
    public static function createFromJira($jira, $options = [])
    {
        // Create a new issue type
        $issueType = new static;

        // Update the issue type from jira
        return $issueType->updateFromJira($jira, $options);
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
            $this->jira_id = $jira->schema->customId ?? null;
            $this->jira_key = $jira->key;
            $this->display_name = $jira->name;
            $this->schema_type = $jira->schema->type;
            $this->schema_items = $jira->schema->items ?? null;
            $this->schema_system = $jira->schema->system ?? null;
            $this->schema_custom = $jira->schema->custom ?? null;
            $this->operations = $jira->operations ?? [];
            $this->auto_complete_url = $jira->autoCompleteUrl ?? null;
            $this->has_default_value = $jira->hasDefaultValue;
            $this->default_value = optional($jira->defaultValue ?? null)->id;
            $this->required = $jira->required;

            // Assign the allowed values
            $this->allowed_values = array_reduce($jira->allowedValues ?? [], function($values, $value) {

                // Convert each value to a key/value pair
                $values[$value->value ?? $value->name] = $value->id;

                // Return the values
                return $values;

            }, []);

            // Save
            $this->save();

            // Allow chaining
            return $this;

        });
    }

    /** 
     * Returns the issue types associated to this issue field.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function issueTypes()
    {
        return $this->belongsToMany(IssueType::class, 'issue_type_fields', 'issue_field_id', 'issue_type_id');
    }

    /** 
     * Returns the project associated to this issue field.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
