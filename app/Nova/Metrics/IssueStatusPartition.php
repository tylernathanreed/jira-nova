<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Partition;

class IssueStatusPartition extends Partition
{
    use Concerns\Nameable;
    use Concerns\QueryCallbacks;

    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'partition-metric';

    /**
     * The displayable name of the metric.
     *
     * @var string
     */
    public $name = 'Issues by Status';

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new query
        $query = (new Issue)->newQuery();

        // Apply the filter
        $this->applyQueryCallbacks($query);

        // Determine the result
        $result = $this->count($request, $query, 'status_name');

        // Condense the values
        $result->value = [

            'Grooming' => (
                ($result->value['In Design'] ?? 0) +
                ($result->value['New'] ?? 0) +
                ($result->value['Dev Help Needed'] ?? 0) +
                ($result->value['Need Client Clarification'] ?? 0) +
                ($result->value['Waiting for approval'] ?? 0)
            ),

            'Ready' => (
                ($result->value['Assigned'] ?? 0) +
                ($result->value['Dev Hold'] ?? 0)
            ),

            'In Progress' => (
                ($result->value['Dev Complete'] ?? 0) +
                ($result->value['In Development'] ?? 0) +
                ($result->value['Testing Failed'] ?? 0)                
            ),

            'Testing' => (
                ($result->value['Ready to Test [Test]'] ?? 0) +
                ($result->value['Ready to test [UAT]'] ?? 0) +
                ($result->value['Test Help Needed'] ?? 0)
            ),

            'Done' => (
                ($result->value['Cancelled'] ?? 0) +
                ($result->value['Done'] ?? 0) +
                ($result->value['Testing Passed [Test]'] ?? 0)
            )

        ];

        // Assign the colors
        $result->colors([
            'Grooming' => '#f99037',
            'Ready' => '#5b9bd5',
            'In Progress' => '#ffc000',
            'Testing' => '#9c6ade',
            'Done' => '#098f56',
        ]);

        // Return the result
        return $result;
    }
}
