<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:31
 */

namespace JiraAgileRestApi\Issue;

class Issue implements \JsonSerializable
{
    /**
     * return only if Project query by key(not id).
     *
     * @var string|null
     */
    public $expand;

    /** @var string */
    public $self;

    /** @var string */
    public $id;

    /** @var string */
    public $key;

    /** @var IssueField */
    public $fields;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}