<?php

namespace App\Support\Jira;

use JiraRestApi\User\UserService;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\ProjectService;

class JiraService
{
	/**
	 * Returns the issue service.
	 *
	 * @return \JiraRestApi\User\IssueService
	 */
	public function issues()
	{
		return new IssueService;
	}

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