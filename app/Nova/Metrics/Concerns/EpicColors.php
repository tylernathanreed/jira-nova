<?php

namespace App\Nova\Metrics\Concerns;

use App\Models\Issue;

trait EpicColors
{
    /**
     * Assigns epic colors to the specified result.
     *
     * @param  mixed  $result
     *
     * @return mixed
     */
    public function assignEpicColors($result)
    {
        // Determine the epic colors
        $colors = $this->getEpicColors();

        // Assign the colors
        $result->colors($colors);

        // Return the result
        return $result;
    }

    /**
     * Returns the epic colors.
     *
     * @return array
     */
    public function getEpicColors()
    {
        // Determine the epic colors
        $colors = (new Issue)->select(['epic_name', 'epic_color'])->whereNotNull('epic_name')->distinct()->getQuery()->get()->pluck('epic_color', 'epic_name');

        // Determine the epic color hex map
        $map = Issue::getEpicColorHexMap();

        // Map the colors into hex values
        $colors->transform(function($color) use ($map) {
            return $map[$color ?? 'ghx-label-0'] ?? '#000';
        });

        // Add the "Other" color
        $colors['Other'] = '#777';

        // Reduce the color list to only present values
        $colors = $colors->only(array_keys($value));

        // Initialize the color counts
        $counts = $colors->flip()->map(function($color) {
            return 0;
        });

        // If a color is repeated, force a different color
        $colors->transform(function($color, $epic) use (&$counts) {

            // Increase the color count
            $counts[$color] = $counts[$color] + 1;

            // If this is the first of its kind, keep it
            if($counts[$color] == 1) {
                return $color;
            }

            // Detemrine the count
            $count = $counts[$color];

            // Offset each digit
            return '#' . implode('', array_map(function($v) use ($count) {
                return dechex((hexdec($v) - $count + 16) % 16);
            }, str_split(substr($color, 1))));

        });

        // Return the colors
        return $colors->all();
    }
}