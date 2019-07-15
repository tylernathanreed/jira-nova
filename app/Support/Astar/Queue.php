<?php

namespace App\Support\Astar;

use SplPriorityQueue;

class Queue extends SplPriorityQueue
{
	/**
	 * Compare priorities in order to place elements correctly in the heap while sifting up.
	 *
	 * @param  mixed  $priority1
	 * @param  mixed  $priority2
	 *
	 * @return integer
	 */
    public function compare($priority1, $priority2)
    {
    	// Compare the priorities in reverse order so that the lowest priority is on top
        return parent::compare($priority2, $priority1);
    }
}