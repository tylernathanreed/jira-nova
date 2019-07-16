<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 01:10
 */

namespace JiraAgileRestApi\Sprint;


class SprintIssue implements \JsonSerializable
{
    /** @var string[] */
    public $issues;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}