<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:50
 */

namespace JiraAgileRestApi\BacklogIssue;

use JiraAgileRestApi\JiraClient;

class BacklogIssueService extends JiraClient
{
    private $uri = '/backlog/issue';

    /**
     * @param BacklogIssue $backlogIssue
     * @return string
     * @throws \JiraAgileRestApi\JiraException
     */
    public function create(BacklogIssue $backlogIssue)
    {
        $data = json_encode($backlogIssue);
        $this->log->addInfo("Move issues to backlog=\n".$data);
        $ret = $this->exec($this->uri."/", $data, 'POST');
        return $ret;
    }
}