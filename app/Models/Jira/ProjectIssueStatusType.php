<?php

namespace App\Models\Jira;

class ProjectIssueStatusType extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'project_issue_status_types';

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
        static::loadRecordMapIfNotLoaded(WorkflowStatusType::class);

        // Determine associated records
        $project = static::getRecordFromMap(Project::class, $record->project);
        $issueType = static::getRecordFromMap(IssueType::class, $record->issueType);
        $status = static::getRecordFromMap(WorkflowStatusType::class, $record->id);

        // Update the jira attributes
        $this->project()->associate($project);
        $this->issueType()->associate($issueType);
        $this->workflowStatusType()->associate($status);

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
        return $record->project . '@' . $record->issueType . '@' . $record->id;
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

            // Find the next page of records by issue type
            $issueTypes = $connection->getProjectStatuses($project->jira_key);

            // Iterate through each issue type
            foreach($issueTypes as $type) {

                // Determine the next page of records
                $records = $type->statuses;

                // Assign the project and issue type to each record
                foreach($records as $record) {

                    $record->project = $project->jira_key;
                    $record->issueType = $type->id;

                }

                // Yield the records
                yield $records;

            }

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

    /**
     * Returns the status type that this pivot belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workflowStatusType()
    {
        return $this->belongsTo(WorkflowStatusType::class, 'workflow_status_type_id');
    }
}
