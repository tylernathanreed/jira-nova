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
        // Determine the model class
        $model = $this->model_class;

        // Determine the operation
        $operation = is_null($since) ? 'build' : 'update';

        // Set when the operation began
        $this->setAttribute("{$operation}_started_at", carbon());
        $this->setAttribute("{$operation}_completed_at", null);

        // Initialize the operation counts
        $this->setAttribute("{$operation}_record_count", 0);
        $this->setAttribute("{$operation}_record_total", 0);

        // Update the status
        $this->status = $operation == 'build' ? static::STATUS_BUILDING : static::STATUS_UPDATING;

        // Save this cache
        $this->save();

        // Determine the cache pages
        $pages = $model::getCachePages(carbon($since));

        // Update the record total
        $this->setAttribute("{$operation}_record_total", head($pages)['total']);

        /**
         * @todo Event for record total
         */

        // Save this cache
        $this->save();

        // Iterate through each page
        foreach($pages as $page) {

            // Handle each page
            call_user_func($page['perform'], $page['records']);

            // Update the operation statistics
            $this->setAttribute("{$operation}_record_count", $page['current']);
            $this->setAttribute("{$operation}_record_total", $page['total']);

            // Save this cache
            $this->save();

            // Fire the update event
            event(new CacheStatusUpdate($model, $operation, $page['current'], $page['total']));

        }

        // Mark the operation as completed
        $this->setAttribute("{$operation}_completed_at", carbon());

        // Update the status
        $this->status = $operation == 'build' ? static::STATUS_BUILT : static::STATUS_UPDATING;

        /**
         * @todo Event for complete.
         */

        // Save this cache
        $this->save();

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
