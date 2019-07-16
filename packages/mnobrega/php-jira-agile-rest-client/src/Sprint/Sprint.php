<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 00:32
 */

namespace JiraAgileRestApi\Sprint;


class Sprint implements \JsonSerializable
{
    const STATE_CLOSED = 'closed';
    const STATE_FUTURE = 'future';
    const STATE_ACTIVE = 'active';

    /** @var integer */
    public $id;
    /** @var string */
    public $self;
    /** @var string */
    public $state;
    /** @var string */
    public $name;
    /** @var string|null */
    public $startDate;
    /** @var string|null */
    public $endDate;
    /** @var integer */
    public $originBoardId;
    /** @var string */
    public $completeDate;

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function setCompletedDate($completeDate)
    {
        $this->completeDate = $completeDate;
        return $this;
    }

    public function setOriginBoardId($originBoardId)
    {
        $this->originBoardId = $originBoardId;
        return $this;
    }

    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}