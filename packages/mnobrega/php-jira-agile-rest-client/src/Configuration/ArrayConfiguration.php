<?php
/**
 * Created by PhpStorm.
 * User: keanor
 * Date: 17.08.15
 * Time: 22:40.
 */

namespace JiraAgileRestApi\Configuration;
use JiraAgileRestApi\JiraException;

/**
 * Class ArrayConfiguration.
 */
class ArrayConfiguration extends AbstractConfiguration
{


    /**
     * ArrayConfiguration constructor.
     * @param array $configuration
     * @throws JiraException
     */
    public function __construct(array $configuration)
    {
        $this->jiraLogFile = 'jira-agile-rest-client.log';
        $this->jiraLogLevel = 'WARNING';
        $this->curlOptSslVerifyHost = false;
        $this->curlOptSslVerifyPeer = false;
        $this->curlOptVerbose = false;

        foreach ($configuration as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
