<?php

namespace App\Nova\Metrics\Results;

use Laravel\Nova\Metrics\PartitionResult;

class RelativeStatusPartitionResult extends PartitionResult
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
        // Order the values
        $value = [
            'Not Started' => $value['Not Started'] ?? 0,
            'Not Prioritized' => $value['Not Prioritized'] ?? 0,
            'Stuck' => $value['Stuck'] ?? 0,
            'At Risk' => $value['At Risk'] ?? 0,
            'On Target' => $value['On Target'] ?? 0,
        ];

        // Call the parent method
        parent::__construct($value);

        // Assign the colors
        $this->colors(static::getPartitionColors());
    }

    /** 
     * Returns the epic colors.
     *
     * @return array
     */
    public static function getPartitionColors()
    {
        return [
            'Not Started' => '#dfe1e6',
            'Not Prioritized' => '#998dd9',
            'Stuck' => '#ffc400',
            'At Risk' => '#ff8252',
            'Past Due' => '#ff5252',
            'On Target' => '#00875a'
        ];
    }
}
