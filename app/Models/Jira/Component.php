<?php

namespace App\Models\Jira;

class Component extends Model
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
        // Load the users if not already loaded
        static::loadRecordMapIfNotLoaded(User::class);

        // Load the projects if not already loaded
        static::loadRecordMapIfNotLoaded(Project::class);

        // Determine the project
        $project = static::getRecordFromMap(Project::class, $record->project);

        // Determine the user associations
        $lead = static::getRecordFromMap(User::class, optional($record->lead ?? null)->accountId);
        $assignee = static::getRecordFromMap(User::class, optional($record->assignee ?? null)->accountId);
        $realAssignee = static::getRecordFromMap(User::class, optional($record->realAssignee ?? null)->accountId);

        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->project()->associate($project);
        $this->name = $record->name;
        $this->description = $record->description ?? null;
        $this->lead()->associate($lead);
        $this->assignee_type = $record->assigneeType;
        $this->assignee()->associate($assignee);
        $this->real_assignee_type = $record->realAssigneeType;
        $this->realAssignee()->associate($realAssignee);
        $this->is_assignee_type_valid = $record->isAssigneeTypeValid;

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
        return $record->project . '@' . $record->id;
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

            // Initialize the pagination variables
            $startAt = 0;
            $maxResults = 50;

            // Start the first iteration
            do {

                // Find the next page of records
                $records = $connection->getProjectComponentsPaginated($project->jira_key, compact('startAt', 'maxResults'))->values;

                // Advance the starting position
                $startAt += count($records);

                // Yield the records
                yield $records;

            }

            // Loop until there aren't any more records, or a partial page is found
            while(count($records) >= $maxResults);

        }
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this component belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the user assigned as lead to this component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    /**
     * Returns the user assigned as assignee to this component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Returns the user assigned as real assignee to this component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function realAssignee()
    {
        return $this->belongsTo(User::class, 'real_assignee_id');
    }
}
