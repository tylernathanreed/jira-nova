<?php

namespace App\Models\Jira;

class WorkflowStatusCategory extends Model
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
        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->jira_key = $record->key;
        $this->name = $record->name;
        $this->color_name = $record->colorName;

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
        return $record->key;
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
        $records = $connection->getStatusCategories();

        // Yield the records
        yield $records;
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the workflow status types that use this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflowStatusTypes()
    {
        return $this->hasMany(WorkflowStatusType::class, 'workflow_status_category_id');
    }
}
