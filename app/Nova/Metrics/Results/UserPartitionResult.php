<?php

namespace App\Nova\Metrics\Results;

use Closure;
use App\Models\FocusGroup;
use Laravel\Nova\Metrics\PartitionResult;

class UserPartitionResult extends PartitionResult
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
        $value = array_filter($value, function($aggregate) {
            return $aggregate > 0;
        });

        // Call the parent method
        parent::__construct($value);

        // Assign the colors
        $this->colors(static::getUserColors());

        // Assign the labels
        $this->label(static::getUserLabelResolver());
    }

    /**
     * Returns the focus group colors.
     *
     * @return array
     */
    public static function getUserColors()
    {
        // Determine the default colors
        $colors = static::getDefaultColors();

        // Add the "Unassigned" color
        $colors['Unassigned'] = $colors['Unassigned'] ?? '#777';

        // Return the colors
        return $colors;
    }

    /**
     * Returns the default colors.
     *
     * @return array
     */
    public static function getDefaultColors()
    {
        return [
            '#F5573B',
            '#F99037',
            '#F2CB22',
            '#8FC15D',
            '#098F56',
            '#47C1BF',
            '#1693EB',
            '#6474D7',
            '#9C6ADE',
            '#E471DE'
        ];
    }

    /**
     * Returns the user label resolver.
     *
     * @return \Closure
     */
    public static function getUserLabelResolver()
    {
        return function($label) {

            // Check for multiple names
            if(strpos($label, ' ') !== false) {

                // Change the label to use "First L." convention
                $label = array_reduce(explode(' ', $label), function($label, $part) {
                    return empty($label) ? $part : $label .= ' ' . strtoupper(substr($part, 0, 1)) . '.';
                }, '');

            }

            // Cast null to "Unassigned"
            return $label ?: 'Unassigned';

        };
    }
}
