<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 21/01/2018
 * Time: 16:14
 */

namespace JiraAgileRestApi\IssueRank;

use JiraAgileRestApi\Issue\Issue;

class IssueRank implements \JsonSerializable
{
    /** @var string[] */
    public $issues;
    /** @var string */
    public $rankBeforeIssue;
    /** @var string */
    public $rankCustomFieldId;

    public function addIssue($issueKey)
    {
        $this->issues[] = $issueKey;
        return $this;
    }

    public function setRankBeforeIssue($issueKey)
    {
        $this->rankBeforeIssue = $issueKey;
        return $this;
    }

    public function setRankCustomFieldId($rankCustomFieldId)
    {
        $this->rankCustomFieldId = $rankCustomFieldId;
        return $this;
    }

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}