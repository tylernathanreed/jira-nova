<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 28/01/2018
 * Time: 04:13
 */

namespace JiraAgileRestApi\Board;


class Board implements \JsonSerializable
{
    /** @var integer */
    public $id;
    /** @var string */
    public $self;
    /** @var string */
    public $name;
    /** @var string */
    public $type;

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}