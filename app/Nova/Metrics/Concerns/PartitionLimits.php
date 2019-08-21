<?php

namespace App\Nova\Metrics\Concerns;

use Laravel\Nova\Metrics\PartitionResult;

trait PartitionLimits
{
    /**
     * Limits the results of the specified partition result.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     * @param  integer                                $limit
     * @param  string|null                            $label
     *
     * @return \Laravel\Nova\Metrics\PartitionResult
     */
    public function limitPartitionResult(PartitionResult $result, $limit = 10, $label = null)
    {
        // Determine the result value
        $value = $result->value;

        // Sort the result by workload
        arsort($value);

        // Merge the last results into a labelled category
        $value = array_merge(array_slice($value, 0, $limit - 1), [($label ?? 'Other') => array_sum(array_slice($value, $limit - 1))]);

        // Update the value
        $result->value = $value;

        // Return the result
        return $result;
    }    
}