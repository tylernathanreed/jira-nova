<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 22/01/2018
 * Time: 01:04
 */

namespace JiraAgileRestApi\Sprint;

use JiraAgileRestApi\JiraClient;

class SprintService extends JiraClient
{
    private $uri = "/sprint";

    /**
     * @param $sprintId
     * @return Sprint|object
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function get($sprintId)
    {
        $ret = $this->exec($this->uri."/$sprintId", null);
        $this->log->addInfo('Result='.$ret);
        $sprint = $this->json_mapper->map(
            json_decode($ret), new Sprint()
        );
        return $sprint;
    }

    /**
     * @param Sprint $sprint
     * @return Sprint|object
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function create(Sprint $sprint)
    {
        /** WHY: not allowed parameters in a POST */
        $sprint->setState(null)->setCompletedDate(null);
        $data = json_encode($sprint);
        $this->log->addInfo("Create sprint=\n".$data);
        $ret = $this->exec($this->uri,$data,'POST');
        $sprint = $this->json_mapper->map(
            json_decode($ret), new Sprint()
        );
        return $sprint;
    }

    /**
     * @param $sprintId
     * @param Sprint $sprint
     * @return string
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function update($sprintId, Sprint $sprint)
    {
        /** WHY: cant move a sprint from board A to board B */
        $sprint->setOriginBoardId(null);
        $data = json_encode($sprint);
        $this->log->addInfo("Update Sprint=\n".$data);
        $ret = $this->exec($this->uri."/$sprintId", $data, 'PUT');
        $sprint = $this->json_mapper->map(
            json_decode($ret), new Sprint()
        );
        return $sprint;
    }

    /**
     * @param $sprintId
     * @return string
     * @throws \JiraAgileRestApi\JiraException
     */
    public function delete($sprintId)
    {
        $this->log->addInfo("deleteSprint=\n");
        $ret = $this->exec($this->uri."/$sprintId", '', 'DELETE');
        $this->log->addInfo('delete sprint '.$sprintId.' result='.var_export($ret, true));
        return $ret;
    }

    /**
     * @param $sprintId
     * @param SprintIssue $sprintIssue
     * @return string
     * @throws \JiraAgileRestApi\JiraException
     */
    public function addIssues($sprintId, SprintIssue $sprintIssue)
    {
        $data = json_encode($sprintIssue);
        $this->log->addInfo("Move issues to sprintg=\n".$data);
        $ret = $this->exec($this->uri."/$sprintId/issue", $data, 'POST');
        return $ret;
    }


}