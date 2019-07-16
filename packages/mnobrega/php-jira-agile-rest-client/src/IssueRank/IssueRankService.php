<?php

namespace JiraAgileRestApi\IssueRank;

use JiraAgileRestApi\JiraException;
use JiraAgileRestApi\JiraClient;

class IssueRankService extends JiraClient
{
    private $uri = '/issue/rank';

    /**
     * @param $issueRank IssueRank
     * @return string
     * @throws JiraException
     */
    public function update(IssueRank $issueRank)
    {
        $data = json_encode($issueRank);
        $this->log->addInfo("Update Rank=\n".$data);
        $ret = $this->exec($this->uri."/", $data, 'PUT');
        return $ret;
    }
}
