<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 28/01/2018
 * Time: 04:08
 */

namespace JiraAgileRestApi\Sprint;


class SprintSearchResult implements \JsonSerializable
{
    /** @var integer */
    public $maxResults;
    /** @var integer */
    public $startAt;
    /** @var boolean */
    public $isLast;
    /** @var Sprint[]|null */
    public $values;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}