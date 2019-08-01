<?php

namespace App\Nova\Metrics;

use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class JiraIssueByPriorityPartition extends Partition
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'resource-partition-metric';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Determine the isssues
        $issues = collect(json_decode($request->resourceData, true));

        // Determine the counts by priority
        $counts = $issues->countBy('priority')->all();

        // Determine the results
        $results = [
            'Highest' => $counts['Highest'] ?? 0,
            'High' => $counts['High'] ?? 0,
            'Medium' => $counts['Medium'] ?? 0,
            'Low' => $counts['Low'] ?? 0,
            'Lowest' => $counts['Lowest'] ?? 0
        ];

        // Return the partition result
        return $this->result($results)->colors([
            'Highest' => 'firebrick',
            'High' => '#f44',
            'Medium' => 'silver',
            'Low' => 'mediumseagreen',
            'Lowest' => 'green'
        ]);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'jira-issue-by-priorities';
    }

    /**
     * Get the displayable name of the metric.
     *
     * @return string
     */
    public function name()
    {
        return 'Priorities';
    }

}
