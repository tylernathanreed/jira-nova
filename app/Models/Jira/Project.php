<?php

namespace App\Models\Jira;

class Project extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        'project_keys' => 'array',
        'properties' => 'array'
    ];

    ///////////////
    //* Caching *//
    ///////////////
    /**
     * Updates this user from jira.
     *
     * @param  mixed  $jira
     * @param  array  $options
     *
     * @return $this
     */
    public function updateFromJira($record, $options = [])
    {
        // Load the users if not already loaded
        $this->loadRecordMapIfNotLoaded(User::class);

        // Determine the project lead
        $lead = static::getRecordFromMap(User::class, optional($record->lead)->accountId);

        // Update the jira attributes
        $this->jira_id = $record->id;
        $this->jira_key = $record->key;
        $this->entity_id = $record->entityId ?? null;
        $this->uuid = $record->uuid ?? null;
        $this->style = $record->style;
        $this->name = $record->name;
        $this->description = $record->description ?: null;
        $this->avatar_urls = $record->avatarUrls;
        $this->lead()->associate($lead);
        $this->project_keys = $record->projectKeys;
        $this->project_type_key = $record->projectTypeKey;
        $this->is_simplified = $record->simplified;
        $this->is_private = $record->isPrivate;
        $this->properties = ((array) $record->properties) ?: null;

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
        return $record->uuid ?? $record->id;
    }

    /**
     * Returns the paginated records using the specified connection.
     *
     * @param  \App\Support\Jira\Api\Connection  $connection
     *
     * @return array
     */
    public static function getPaginatedJiraRecords($connection)
    {
        // Initialize the pagination variables
        $startAt = 0;
        $maxResults = 50;

        // Determine the properties to expand
        $expand = [
            'description',
            'lead',
            'projectKeys'
        ];

        // Start the first iteration
        do {

            // Find the next page of records
            $records = $connection->getProjects(compact('startAt', 'maxResults', 'expand'))->values;

            // Advance the starting position
            $startAt += count($records);

            // Yield the records
            yield $records;

        }

        // Loop until there aren't any more records, or a partial page is found
        while(count($records) >= $maxResults);
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the user assigned as lead to this project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lead()
    {
        return $this->belongsTo(User::class, 'lead_id');
    }
}
