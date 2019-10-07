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
     * Returns the default query callbacks.
     *
     * @return array
     */
    public function getDefaultCallbacks()
    {
        // Determine the week label
        $label = $this->getWeekLabel($this->reference ? carbon($this->reference) : carbon());

        // Return the default callbacks
        return [
            function($query) use ($label) {
                $query->where('labels', 'like', "%\"{$label}%");
            }
        ];
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
