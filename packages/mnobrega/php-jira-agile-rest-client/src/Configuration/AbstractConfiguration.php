<?php

namespace JiraAgileRestApi\Configuration;

use JiraRestApi\Configuration\AbstractConfiguration as BaseAbstractConfiguration;

/**
 * Class AbstractConfiguration.
 */
abstract class AbstractConfiguration extends BaseAbstractConfiguration
{
    /**
     * Jira Version.
     *
     * @var string
     */
    protected $jiraVersion;

    public function getJiraVersion()
    {
        return $this->jiraVersion;
    }
}
