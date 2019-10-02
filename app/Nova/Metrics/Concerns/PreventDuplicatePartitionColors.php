<?php

namespace App\Nova\Metrics\Concerns;

use Laravel\Nova\Metrics\PartitionColors;

trait PreventDuplicatePartitionColors
{
    /**
     * Set the custom label colors.
     *
     * @param  array  $colors
     *
     * @return $this
     */
    public function colors(array $colors)
    {
        // Convert the colors to a collection
        $colors = collect($colors);

        // We're going to prevent duplicative colors, so first we have to
        // know what colors we'll be displaying, and then count how many
        // times each color appears. We'll only slightly change them.

        // Determine the value labels
        $labels = array_keys($this->value);

        // Reduce the color list to only present value labels
        $colors = collect($colors)->only($labels);

        // Initialize the color counts
        $counts = $colors->flip()->map(function($color) {
            return 0;
        });

        // If a color is repeated, force a different color
        $colors->transform(function($color, $label) use (&$counts) {

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

        // Assign the colors
        $this->colors = new PartitionColors($colors->all());

        // Allow chaining
        return $this;
    }
}