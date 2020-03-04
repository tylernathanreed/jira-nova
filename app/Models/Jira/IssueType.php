<?php

namespace App\Models\Jira;

class IssueType extends Model
{
    ///////////////
    //* Caching *//
    ///////////////
    /**
     * Updates this model from jira.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *z
     * @return $this
     */
    public function updateFromJira($record, $options = [])
    {
        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->entity_id = $record->entityId ?? null;
        $this->name = $record->name;
        $this->description = ($record->description ?? null) ?: null;
        $this->subtask = $record->subtask ?? false;
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
        return $record->entityId ?? $record->id;
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
        // Iterate through each project
        foreach(Project::cursor() as $project) {

            // Find the next page of records
            $records = $connection->getProject($project->jira_key, ['expand' => 'issueTypes'])->issueTypes;

            // Yield the records
            yield $records;

        }
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this issue type belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
