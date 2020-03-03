<?php

namespace App\Models\Jira;

class User extends Model
{
    //////////////////
    //* Record Map *//
    //////////////////
    /**
     * Returns the record map key name for this model.
     *
     * @return \Closure|string
     */
    public function getRecordMapKeyName()
    {
        return 'account_id';
    }

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
        // Update the jira attributes
        $this->account_id = $record->accountId;
        $this->account_type = $record->accountType;
        $this->avatar_urls = $record->avatarUrls;
        $this->display_name = $record->displayName;
        $this->is_active = !! $record->active;
        $this->timezone = $record->timeZone ?? null;
        $this->locale = $record->locale ?? null;

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
        return "{$record->accountType}@{$record->accountId}";
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
        // Initialize the pagination variables
        $startAt = 0;
        $maxResults = 50;

        // Start the first iteration
        do {

            // Find the next page of records
            $records = $connection->getUsers(compact('startAt', 'maxResults'));

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
     * Returns the projects where this user is assigned as the lead.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectsAsLead()
    {
        return $this->hasMany(Project::class, 'lead_id');
    }
}
