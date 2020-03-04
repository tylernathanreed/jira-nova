<?php

namespace App\Models\Jira;

class WorkflowStatusType extends Model
{
    ///////////////
    //* Caching *//
    ///////////////
    /**
     * Updates this model from jira.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *
     * @return $this
     */
    public function updateFromJira($record, $options = [])
    {
        // Load the projects if not already loaded
        static::loadRecordMapIfNotLoaded(WorkflowStatusCategory::class);

        // Determine the project
        $category = static::getRecordFromMap(WorkflowStatusCategory::class, $record->statusCategory->key);

        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->workflowStatusCategory()->associate($category);
        $this->name = $record->name;
        $this->description = $record->description ?: null;
        $this->icon_url = $record->iconUrl;

        // Save
        $this->save();

        // Allow chaining
        return $this;
    }

    /**
     * Returns the jira cache key from the specified api record.
     *
     * @return string
     */
    public static function getJiraCacheKeyFromApi($record)
    {
        return $record->id;
    }

    /**
     * Returns the paginated records using the specified connection.
     *
     * @param  \App\Support\Jira\Api\Connection  $connection
     *
     * @return \Generator
     */
    public static function getPaginatedJiraRecords($connection)
    {
        // Find the next page of records
        $records = $connection->getStatuses();

        // Yield the records
        yield $records;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the category that this status type belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workflowStatusCategory()
    {
        return $this->belongsTo(WorkflowStatusCategory::class, 'workflow_status_category_id');
    }

    /**
     * Returns the projects associated to this status type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function projects()
    {
        $instance = new ProjectIssueStatusType;

        return $this->belongsToMany(Project::class, $instance->getTable(), 'workflow_status_type_id', 'project_id')
            ->using(ProjectIssueStatusType::class);
    }
}
