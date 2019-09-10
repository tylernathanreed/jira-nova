<?php

namespace App\Nova\Dashboards;

class GroomingDashboard extends Dashboard
{
    use Concerns\StatusMetrics;

    /**
     * The displayable name for this dashboard.
     *
     * @var string
     */
    protected static $label = 'Grooming';

    /** 
     * The prior status types that indicate standard workflow.
     *
     * @var array
     */
    protected static $priorStatuses = [
        'New'
    ];

    /** 
     * The primary status types for this dashboard.
     *
     * @var array
     */
    protected static $statuses = [
        'In Design',
        'Need Client Clarification',
        'Dev Help Needed',
        'Waiting for approval',
        'Validating'
    ];

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            static::getKickbacksValueMetric(),
            static::getKickbacksTrendMetric()->width('2/3'),

            static::getInflowTrendMetric(),
            static::getOutflowTrendMetric(),
            static::getEquilibriumTrendMetric(),

            static::getActualDelinquenciesTrendMetric(),
            static::getEstimatedDelinquenciesTrendMetric(),
            static::getSatisfactionValueMetric(),

            static::getWorkloadByEpicPartitionMetric(),
            static::getWorkloadByPriorityPartitionMetric(),
            static::getWorkloadByAssigneePartitionMetric()
        ];
    }
}
