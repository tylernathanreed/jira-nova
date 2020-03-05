<?php

namespace App\Models\Jira;

class Version extends Model
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
        static::loadRecordMapIfNotLoaded(Project::class, 'jira_id');

        // Determine the project
        $project = static::getRecordFromMap(Project::class, $record->projectId, 'jira_id');

        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->project()->associate($project);
        $this->name = $record->name;
        $this->description = $record->description ?? null;
        $this->archived = $record->archived ?? false;
        $this->released = $record->released ?? false;
        $this->start_date = $record->startDate ?? null;
        $this->release_date = $record->releaseDate ?? null;
        $this->overdue = $record->overdue ?? false;

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
        // Iterate through each project
        foreach(Project::cursor() as $project) {

            // Find the next page of records
            $records = $connection->getProjectVersions($project->jira_key);

            // Yield the records
            yield $records;

        }
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this version belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
