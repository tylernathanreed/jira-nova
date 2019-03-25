<?php

namespace App\Support\Jira;

use JiraRestApi\User\UserService;

class JiraService
{
	/**
	 * Returns the user service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function users()
	{
		return new UserService;
	}
}