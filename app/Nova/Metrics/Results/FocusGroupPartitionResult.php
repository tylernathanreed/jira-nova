<?php

namespace App\Nova\Metrics\Results;

use Closure;
use App\Models\FocusGroup;
use Laravel\Nova\Metrics\PartitionResult;

class FocusGroupPartitionResult extends PartitionResult
{
    use Concerns\PreventDuplicatePartitionColors;

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
        $this->colors(static::getFocusGroupColors());

        // Assign the labels
        $this->label(static::getFocusGroupLabelResolver());
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
        $order = static::getFocusGroupOrder();

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
     * Returns the focus group order.
     *
     * @return array
     */
    public static function getFocusGroupOrder()
    {
        return FocusGroup::all()->sortBy('display_order')->pluck('system_name')->toArray();
    }

    /**
     * Returns the focus group colors.
     *
     * @return array
     */
    public static function getFocusGroupColors()
    {
        return FocusGroup::all()->pluck('color.primary', 'system_name')->toArray();
    }

    /**
     * Returns the focus group label resolver.
     *
     * @return \Closure
     */
    public static function getFocusGroupLabelResolver()
    {
        // Determine the focus groups
        $groups = FocusGroup::all()->keyBy('system_name');

        // Return the label resolver
        return function($label) use ($groups) {
            return isset($groups[$label]) ? $groups[$label]->display_name : $label;
        };
    }
}
