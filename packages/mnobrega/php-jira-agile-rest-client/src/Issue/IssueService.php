<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:36
 */

namespace JiraAgileRestApi\Issue;

use JiraAgileRestApi\JiraClient;

class IssueService extends JiraClient
{
    private $uri = '/issue';

    /**
     * @param $issueIdOrKey
     * @param array $paramArray
     * @param null $issueObject
     * @return object
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function get($issueIdOrKey, $paramArray = [], $issueObject = null)
    {
        $issueObject = ($issueObject) ? $issueObject : new Issue();

        $ret = $this->exec($this->uri.'/'.$issueIdOrKey.$this->toHttpQueryParameter($paramArray), null);

        $this->log->addInfo("Result=\n".$ret);

        return $issue = $this->json_mapper->map(
            json_decode($ret), $issueObject
        );
    }
}