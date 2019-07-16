<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:50
 */

namespace JiraAgileRestApi\BacklogIssue;

class BacklogIssue implements \JsonSerializable
{
    /** @var string[] */
    public $issues;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}