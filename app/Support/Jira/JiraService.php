<?php

namespace App\Support\Jira;

use JiraRestApi\User\UserService;
use App\Support\Jira\Query\Builder;
use JiraRestApi\Issue\IssueService;
use App\Support\Jira\Query\Connection;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\Priority\PriorityService;
use Illuminate\Contracts\Foundation\Application;
use JiraAgileRestApi\IssueRank\IssueRankService;
use JiraRestApi\Configuration\ConfigurationInterface;

class JiraService
{
	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	/**
	 * The client api configuration.
	 *
	 * @var \JiraRestApi\Configuration\ConfigurationInterface
	 */
	protected $config;

	/**
	 * The services that have been instantiated.
	 *
	 * @var array
	 */
	protected $services = [];

	/**
	 * Creates a new jira service instance.
	 *
	 * @param  \Illuminate\Contracts\Foundation\Application       $app
	 * @param  \JiraRestApi\Configuration\ConfigurationInterface  $config
	 *
	 * @return $this
	 */
	public function __construct(Application $app, ConfigurationInterface $config)
	{
		$this->app = $app;
		$this->config = $config;
	}

	/**
	 * Returns the jira connection.
	 *
	 * @return \App\Support\Jira\JiraConnection
	 */
	public function connection()
	{
		return $this->app->make(JiraConnection::class);
	}

	/**
	 * Returns the issue service.
	 *
	 * @return \JiraRestApi\User\IssueService
	 */
	public function issues()
	{
		return $this->service(IssueService::class);
	}

	/**
	 * Returns the issue rank service.
	 *
	 * @return \JiraAgileRestApi\IssueRank\IssueRankService
	 */
	public function issueRanks()
	{
		return $this->service(IssueRankService::class);
	}

	/**
	 * Returns the priorty service.
	 *
	 * @return \JiraRestApi\Priority\PriorityService
	 */
	public function priorities()
	{
		return $this->service(PriorityService::class);
	}

	/**
	 * Returns the project service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function projects()
	{
		return $this->service(ProjectService::class);
	}

	/**
	 * Returns the user service.
	 *
	 * @return \JiraRestApi\User\UserService
	 */
	public function users()
	{
		return $this->service(UserService::class);
	}

	/**
	 * Creates and returns a new issue query.
	 *
	 * @return \App\Support\Query\Builder
	 */
	public function newQuery()
	{
		return new Builder(new Connection($this->issues()));
	}

	/**
	 * Returns the specified service.
	 *
	 * @param  string  $class
	 *
	 * @return mixed
	 */
	public function service($class)
	{
		return $this->services[$class] = $this->services[$class] ?? $this->app->make($class, [
			'configuration' => $this->config
		]);
	}

	/**
	 * Returns the configuration for the jira services.
	 *
	 * @return \JiraRestApi\Configuration\ConfigurationInterface
	 */
	public function getConfiguration()
	{
		return $this->config;
	}
}