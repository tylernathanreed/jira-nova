<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:31
 */

namespace JiraAgileRestApi\Issue;

class IssueField implements \JsonSerializable
{
    /** @var \JiraAgileRestApi\Sprint\Sprint|null */
    public $sprint;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}