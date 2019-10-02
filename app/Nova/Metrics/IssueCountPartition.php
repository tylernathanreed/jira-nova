<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class IssueCountPartition extends Partition
{
    /**
     * The grouping mechanism constants.
     *
     * @var string
     */
    const GROUP_BY_ASSIGNEE = 'assignee';
    const GROUP_BY_EPIC = 'epic';
    const GROUP_BY_FOCUS = 'focus';
    const GROUP_BY_LABEL = 'label';
    const GROUP_BY_PRIORITY = 'priority';
    const GROUP_BY_PROJECT = 'project';
    const GROUP_BY_VERSION = 'version';

    /**
     * Concerns.
     */
    use Concerns\InlineFilterable,
        Concerns\Nameable,
        Concerns\PartitionLimits,
        Concerns\QualifiedGroupByPartitionFix;

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
    public $name;

    /**
     * The grouping mechanism for this partition.
     *
     * @var string
     */
    public $groupBy;

    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // Create a new remaining workload query
        $query = (new Issue)->newRemainingWorkloadQuery();

        // Apply the conditions specific to the grouping mechanism
        $this->applyGroupingScope($query);

        // Apply the filter
        $this->applyFilter($query);

        // Set the partition result class
        $this->setPartitionResultClass();

        // Determine the result
        $result = $this->count($request, $query, $this->getGroupByColumn());

        // If the result has been customized, just return it
        if(!is_null($this->resultClass) && $this->resultClass != PartitionResult::class) {
            return $result;
        }

        // Limit the results
        if(is_null($this->resultClass) || $this->resultClass != PartitionResult::class) {
            $this->limitPartitionResult($result);
        }

        // Apply colors
        $this->applyGroupingColors($result);

        // Apply ordering
        $this->applyGroupingOrder($result);

        // Label the results
        $result->label(function($label) {
            return $label ?: 'Unassigned';
        });

        // Return the partition result
        return $result;
    }

    /**
     * Assigns the label to this metric.
     *
     * @return $this
     */
    public function applyGroupingLabel()
    {
        // If a name as already been provided, skip this step
        if(!is_null($this->name)) {
            return $this;
        }

        // Set the name
        $this->name = $this->getGroupingLabel();

        // Allow chaining
        return $this;
    }

    /**
     * Returns the label to assign to this metric.
     *
     * @return string
     */
    public function getGroupingLabel()
    {
        // Determine by grouping mechanism
        switch($this->groupBy) {

            case static::GROUP_BY_ASSIGNEE: return 'Remaining Count (by Assignee)';
            case static::GROUP_BY_EPIC: return 'Remaining Count (by Epic)';
            case static::GROUP_BY_FOCUS: return 'Remaining Count (by Focus)';
            case static::GROUP_BY_LABEL: return 'Remaining Count (by Label)';
            case static::GROUP_BY_PRIORITY: return 'Remaining Count (by Priority)';
            case static::GROUP_BY_PROJECT: return 'Remaining Count (by Project)';
            case static::GROUP_BY_VERSION: return 'Remaining Count (by Version)';

            default: throw new InvalidArgumentException("Grouping label for [{$this->groupBy}] not defined.");

        }
    }

    /**
     * Applies the grouping scope to the specified query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function applyGroupingScope($query)
    {
        // Determine by grouping mechanism
        switch($this->groupBy) {

            // Epic
            case static::GROUP_BY_EPIC:

                // Make sure the issues are part of an epic
                $query->whereNotNull('epic_name');
                break;

            // Label
            case static::GROUP_BY_LABEL:

                // Join into labels
                $query->joinRelation('labels');
                break;

            // Priority
            case static::GROUP_BY_PRIORITY:

                // Ignore hold and missing priorities
                $query->where('priority_name', '!=', 'Hold')->whereNotNull('priority_name');
                break;

            // Project
            case static::GROUP_BY_PROJECT:

                // Join into projects
                $query->joinRelation('project');
                break;

            // Versions
            case static::GROUP_BY_VERSION:

                // Join into fix versions
                $query->joinRelation('versions');
                break;

            // Assignee & Focus
            case static::GROUP_BY_ASSIGNEE:
            case static::GROUP_BY_FOCUS:

                // Do nothing
                break;

            // Unknown
            default:
                throw new InvalidArgumentException("Grouping scope for [{$this->groupBy}] not defined.");

        }
    }

    /**
     * Returns the column to group by.
     *
     * @return string
     */
    public function getGroupByColumn()
    {
        // Determine by grouping mechanism
        switch($this->groupBy) {

            case static::GROUP_BY_ASSIGNEE: return 'assignee_name';
            case static::GROUP_BY_EPIC: return 'epic_name';
            case static::GROUP_BY_FOCUS: return 'focus';
            case static::GROUP_BY_LABEL: return 'labels.name';
            case static::GROUP_BY_PRIORITY: return 'priority_name';
            case static::GROUP_BY_PROJECT: return 'projects.name';
            case static::GROUP_BY_VERSION: return 'versions.name';

            default: throw new InvalidArgumentException("Group by column for [{$this->groupBy}] not defined.");

        }
    }

    /**
     * Applies the colors for the grouping mechanism.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return void
     */
    public function applyGroupingColors($result)
    {
        // Determine the grouping colors
        $colors = $this->getGroupingColors($result);

        // Add the "Other" color
        $colors['Other'] = $colors['Other'] ?? '#777';

        // Assign the grouping colors
        $result->colors($colors);
    }

    /**
     * Applies the colors for the grouping mechanism.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return array
     */
    public function getGroupingColors($result)
    {
        // Determine by grouping mechanism
        switch($this->groupBy) {

            // Epic
            case static::GROUP_BY_EPIC:
                return $this->getEpicColors(array_keys($result->value));

            // Focus
            case static::GROUP_BY_FOCUS:
                return $this->getFocusGroupColors();

            // Priority
            case static::GROUP_BY_PRIORITY:

                return [
                    'Highest' => 'firebrick',
                    'High' => '#f44',
                    'Medium' => 'silver',
                    'Low' => 'mediumseagreen',
                    'Lowest' => 'green'
                ];

            // Assignee, Label, Project, and Version
            case static::GROUP_BY_ASSIGNEE:
            case static::GROUP_BY_LABEL:
            case static::GROUP_BY_PROJECT:
            case static::GROUP_BY_VERSION:
                return $this->getDefaultColors();

            // Unknown
            default:
                throw new InvalidArgumentException("Grouping colors for [{$this->groupBy}] not defined.");

        }
    }

    /**
     * Returns the default colors.
     *
     * @return array
     */
    public function getDefaultColors()
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
     * Applies the colors for the grouping mechanism.
     *
     * @param  \Laravel\Nova\Metrics\PartitionResult  $result
     *
     * @return void
     */
    public function applyGroupingOrder($result)
    {
        // Determine the result value
        $value = $result->value;

        // Determine the "Unassigned" and "Other" entries
        $unassigned = $value[''] ?? null;
        $other = $value['Other'] ?? null;

        // Remove the "Unassigned" and "Other" entries
        unset($value['Other']);

        // Sort the result by workload
        arsort($value);

        // Add the "Unassigned" entry back in
        if(!is_null($unassigned)) {
            $value[''] = $unassigned;
        }

        // Add the "Other" entry back in
        if(!is_null($other)) {
            $value['Other'] = $other;
        }

        // Update the result
        $result->value = $value;
    }

    /**
     * Sets the grouping mechanism for this metric.
     *
     * @param  string  $groupBy
     *
     * @return $this
     */
    public function groupBy($groupBy)
    {
        // Assign the grouping mechanism
        $this->groupBy = $groupBy;

        // Set the name of this metric based on the grouping mechaism
        $this->applyGroupingLabel();

        // Allow chaining
        return $this;
    }

    /**
     * Variants of {@see $this->groupBy()}.
     *
     * @return $this
     */
    public function groupByAssignee() { return $this->groupBy(static::GROUP_BY_ASSIGNEE); }
    public function groupByEpic() { return $this->groupBy(static::GROUP_BY_EPIC); }
    public function groupByFocus() { return $this->groupBy(static::GROUP_BY_FOCUS); }
    public function groupByLabel() { return $this->groupBy(static::GROUP_BY_LABEL); }
    public function groupByPriority() { return $this->groupBy(static::GROUP_BY_PRIORITY); }
    public function groupByProject() { return $this->groupBy(static::GROUP_BY_PROJECT); }
    public function groupByVersion() { return $this->groupBy(static::GROUP_BY_VERSION); }

    /**
     * Sets the partition result class based on the grouping mechanism.
     *
     * @return $this
     */
    public function setPartitionResultClass()
    {
        // Determine by grouping mechanism
        switch($this->groupBy) {

            // Epic
            case static::GROUP_BY_EPIC:
                return $this->resultClass(EpicPartitionResult::class);

            // Focus Group
            case static::GROUP_BY_FOCUS:
                return $this->resultClass(FocusGroupPartitionResult::class);

            // Priority
            case static::GROUP_BY_PRIORITY:
                return $this->resultClass(PriorityPartitionResult::class);

            // Unknown
            default:
                return $this;

        }
    }

    /**
     * Sets the result class to a custom class.
     *
     * @param  string  $class
     *
     * @return $this
     */
    public function resultClass($resultClass)
    {
        $this->resultClass = $resultClass;

        return $this;
    }

    /**
     * Create a new partition metric result.
     *
     * @param  array  $value
     *
     * @return \Laravel\Nova\Metrics\PartitionResult
     */
    public function result(array $value)
    {
        // Determine the result class
        $class = $this->resultClass ?: PartitionResult::class;

        // Create and return the result
        return new $class($value);
    }
}
