<?php

namespace App\Models\Jira;

class ProjectIssueType extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'projects_issue_types';

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
        // Load the record maps if not already loaded
        static::loadRecordMapIfNotLoaded(Project::class);
        static::loadRecordMapIfNotLoaded(IssueType::class);

        // Determine the project
        $project = static::getRecordFromMap(Project::class, $record->project);

        // Determine the issue type
        $issueType = static::getRecordFromMap(IssueType::class, $record->id);

        // Update the jira attributes
        $this->project()->associate($project);
        $this->issueType()->associate($issueType);

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
        return $record->project . '@' . IssueType::getJiraCacheKeyFromApi($record);
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

            // Assign the project to each issue type
            foreach($records as $record) {
                $record->project = $project->jira_key;
            }

            // Yield the records
            yield $records;

        }
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this pivot belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the issue type that this pivot belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function issueType()
    {
        return $this->belongsTo(IssueType::class, 'issue_type_id');
    }
}
