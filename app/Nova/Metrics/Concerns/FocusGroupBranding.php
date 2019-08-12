<?php

namespace App\Nova\Metrics\Concerns;

use App\Models\FocusGroup;
use Laravel\Nova\Metrics\PartitionResult;

trait FocusGroupBranding
{
    /**
     * Brands the specified metric result as a focus group result.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return \Laravel\Nova\Metrics\PartitionResult
     */
    public function brandPartitionResultAsFocusGroups(PartitionResult $result)
    {
        // Determine the focus groups
        $groups = FocusGroup::all()->keyBy('system_name');

        // Format the labels
        $result->label(function($label) use ($groups) {
            return isset($groups[$label]) ? $groups[$label]->display_name : $label;
        });

        // Format the colors
        $result->colors(
            $groups->pluck('color.primary', 'system_name')->toArray()
        );

        // Return the result
        return $result;
    }
}