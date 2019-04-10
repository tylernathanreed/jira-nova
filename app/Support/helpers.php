<?php

use Carbon\Carbon;

if(!function_exists('carbon')) {

	/**
	 * Creates a new carbon instance from the specified datetime.
	 *
	 * @param  string  $when
	 *
	 * @return \Carbon\Carbon
	 */
	function carbon($when = 'now')
	{
		return Carbon::parse($when);
	}

}