<?php

namespace App\Nova\Metrics;

use App\Models\Issue;
use Illuminate\Http\Request;

class IssueWeekStatusPartition extends IssueStatusPartition
{
    use Concerns\WeeklyLabels;

    /**
     * The reference for the current week.
     *
     * @var string|null
     */
    public $reference;

    /**
     * Filters the query by the week label.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return void
     */
    public function applyFilter($query)
    {
        // Determine the week label
        $label = $this->getWeekLabel($this->reference ? carbon($this->reference) : carbon());

        // Filter the query
        $query->where('labels', 'like', "%\"{$label}%");

        if(!is_null($this->filter)) {
            call_user_func($this->filter, $query);
        }
    }

    /**
     * Returns the name of this metric.
     *
     * @return string
     */
    public function name()
    {
        return $this->name . ' (#' . $this->getWeekLabelIndex($this->reference ? carbon($this->reference) : carbon()) . ')';
    }

    /**
     * Sets the reference of this metric.
     *
     * @param  string  $reference
     *
     * @return $this
     */
    public function reference($reference)
    {
        $this->reference = $reference;

        return $this;
    }
}
