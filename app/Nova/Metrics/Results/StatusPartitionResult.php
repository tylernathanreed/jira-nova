<?php

namespace App\Nova\Metrics\Results;

use Laravel\Nova\Metrics\PartitionResult;

class StatusPartitionResult extends PartitionResult
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
        // Condense the values
        $value = [

            'Grooming' => (
                ($value['In Design'] ?? 0) +
                ($value['New'] ?? 0) +
                ($value['Dev Help Needed'] ?? 0) +
                ($value['Need Client Clarification'] ?? 0) +
                ($value['Waiting for approval'] ?? 0)
            ),

            'Ready' => (
                ($value['Assigned'] ?? 0) +
                ($value['Dev Hold'] ?? 0)
            ),

            'In Progress' => (
                ($value['Dev Complete'] ?? 0) +
                ($value['In Development'] ?? 0) +
                ($value['Testing Failed'] ?? 0)                
            ),

            'Testing' => (
                ($value['Ready to Test [Test]'] ?? 0) +
                ($value['Ready to test [UAT]'] ?? 0) +
                ($value['Test Help Needed'] ?? 0)
            ),

            'Done' => (
                ($value['Cancelled'] ?? 0) +
                ($value['Done'] ?? 0) +
                ($value['Testing Passed [Test]'] ?? 0)
            )

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
            'Grooming' => '#f99037',
            'Ready' => '#5b9bd5',
            'In Progress' => '#ffc000',
            'Testing' => '#9c6ade',
            'Done' => '#098f56',
        ];
    }
}
