<?php

namespace App\Nova\Metrics\Results;

use Closure;
use App\Models\Issue;
use Laravel\Nova\Metrics\PartitionResult;

class EpicPartitionResult extends PartitionResult
{
    use Concerns\PreventDuplicatePartitionColors;

    /**
     * The number of results to show.
     *
     * @var integer
     */
    const RESULT_LIMIT = 5;

    /**
     * Create a new partition result instance.
     *
     * @param  array  $value
     *
     * @return void
     */
    public function __construct(array $value)
    {
        // Sort the result descending by value
        arsort($value);

        // Merge the last results into a labelled category
        if(count($value) >= static::RESULT_LIMIT) {
            $value = array_merge(array_slice($value, 0, static::RESULT_LIMIT - 1), [($label ?? 'Other') => array_sum(array_slice($value, static::RESULT_LIMIT - 1))]);
        }

        // Call the parent method
        parent::__construct($value);

        // Assign the colors
        $this->colors(static::getEpicColors());
    }

    /** 
     * Returns the epic colors.
     *
     * @return array
     */
    public static function getEpicColors()
    {
        return Issue::getEpicColors();
    }
}
