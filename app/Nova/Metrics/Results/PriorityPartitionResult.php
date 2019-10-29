<?php

namespace App\Nova\Metrics\Results;

use Closure;
use App\Models\Issue;
use Laravel\Nova\Metrics\PartitionResult;

class PriorityPartitionResult extends PartitionResult
{
    /**
     * Create a new partition result instance.
     *
     * @param  array  $value
     *
     * @return void
     */
    public function __construct(array $value)
    {
        // Order the value
        $value = static::applyOrderValue($value);

        // Call the parent method
        parent::__construct($value);

        // Assign the colors
        $this->colors(static::getPriorityColors());
    }

    /**
     * Orders the specified value.
     *
     * @param  array  $value
     *
     * @return array
     */
    public static function applyOrderValue($value)
    {
        // Detrmine the order
        $order = static::getPriorityOrder();

        // Initialize the ordered value
        $newValue = [];

        // Add each value in the specified order
        foreach($order as $key) {

            // If the value doesn't exist, don't add it
            if(!array_key_exists($key, $value)) {
                continue;
            }

            // Add the value
            $newValue[$key] = $value[$key];

        }

        // Return the new value
        return $newValue;
    }

    /**
     * Returns the priority order.
     *
     * @return array
     */
    public static function getPriorityOrder()
    {
        return array_keys(Issue::getPriorityColors());
    }

    /**
     * Returns the priority colors.
     *
     * @return array
     */
    public static function getPriorityColors()
    {
        return Issue::getPriorityColors();
    }
}
