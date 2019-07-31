<?php

namespace App\Nova\Metrics\Concerns;

trait WeeklyLabels
{
    /**
     * Returns the week label name.
     *
     * @param  mixed  $when
     *
     * @return string
     */
    public function getWeekLabel($when = 'now')
    {
        // Convert the diff to a label
        return 'Week' . $this->getWeekLabelIndex($when);
    }

    /**
     * Returns the week label epoch date.
     *
     * @return \Carbon\Carbon
     */
    public function getWeekLabelEpoch()
    {
    	return carbon('2019-07-07');
    }

    /**
     * Returns the week label index.
     *
     * @param  mixed  $when
     *
     * @return integer
     */
    public function getWeekLabelIndex($when = 'now')
    {
        // Determine the first week reference
        $start = $this->getWeekLabelEpoch();

        // Determine the current reference
        $when = carbon($when);

        // Return the week diff
        return $start->diffInWeeks($when);
    }
}