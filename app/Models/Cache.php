<?php

namespace App\Models;

use App\Events\CacheStatusUpdate;

class Cache extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The status constants.
     *
     * @var string
     */
    const STATUS_MISSING = 'Missing';
    const STATUS_BUILDING = 'Building';
    const STATUS_BUILT = 'Built';
    const STATUS_UPDATING = 'Updating';
    const STATUS_UPDATED = 'Updated';

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
        'build_started_at',
        'build_completed_at',
        'update_started_at',
        'update_completed_at'
    ];

    /////////////
    //* Cache *//
    /////////////
    /**
     * Busts this cache.
     *
     * @param  mixed  $since
     *
     * @return $this
     */
    public function bust($since = null)
    {
        // Parse the timestamp
        $since = is_null($since) ? null : carbon($since);

        // Determine the model class
        $model = $this->model_class;

        // Determine the operation
        $operation = is_null($since) ? 'build' : 'update';

        // Set when the operation began
        $this->setAttribute("{$operation}_started_at", carbon());
        $this->setAttribute("{$operation}_completed_at", null);

        // Initialize the operation counts
        $this->setAttribute("{$operation}_record_count", 0);
        $this->setAttribute("{$operation}_record_total", $model::getCacheRecordCount($since));

        // Update the status
        $this->status = $operation == 'build' ? static::STATUS_BUILDING : static::STATUS_UPDATING;

        // Save this cache
        $this->save();

        // Fire the status update event
        event(new CacheStatusUpdate($this, $operation));

        // Cache the records
        $model::runCacheHandler(function($current, $total) use ($operation) {

            // Update the operation statistics
            $this->setAttribute("{$operation}_record_count", min($current, $total));
            $this->setAttribute("{$operation}_record_total", $total);

            // Save this cache
            $this->save();

            // Fire the update event
            event(new CacheStatusUpdate($this, $operation));

        }, $since);

        // Mark the operation as completed
        $this->setAttribute("{$operation}_completed_at", carbon());

        // Update the status
        $this->status = $operation == 'build' ? static::STATUS_BUILT : static::STATUS_UPDATED;

        // Save this cache
        $this->save();

        // Fire the update event
        event(new CacheStatusUpdate($this, $operation));

        // Allow chaining
        return $this;
    }

    /**
     * Rebuilds the entire cache.
     *
     * @return $this
     */
    public function rebuild()
    {
        // Flush the update statistics
        $this->update_started_at = null;
        $this->update_completed_at = null;
        $this->update_record_count = null;
        $this->update_record_total = null;
        $this->updates_since_build = 0;

        // Rebuild the cache
        return $this->bust(null);
    }

    /**
     * Updates this cache.
     *
     * @return $this
     */
    public function recache()
    {
        return $this->bust($this->update_completed_at ?: $this->build_completed_at);
    }
}
