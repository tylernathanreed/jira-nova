<?php

namespace App\Support\Jira;

use JiraRestApi\User\UserService;
use JiraRestApi\Project\ProjectService;

class JiraService
{
	/**
	 * Returns the project service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function projects()
	{
		return new ProjectService;
	}

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