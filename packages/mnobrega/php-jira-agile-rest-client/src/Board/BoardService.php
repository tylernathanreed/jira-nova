<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 27/01/2018
 * Time: 00:50
 */

namespace JiraAgileRestApi\Board;

use JiraAgileRestApi\JiraClient;
use JiraAgileRestApi\Sprint\SprintSearchResult;

class BoardService extends JiraClient
{
    private $uri = '/board';

    /**
     * @param array $paramArray
     * @return BoardSearchResult|object
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function getAllBoards($paramArray = [])
    {
        $ret = $this->exec($this->uri.$this->toHttpQueryParameter($paramArray),null);
        return $this->json_mapper->map(
            json_decode($ret), new BoardSearchResult()
        );

    }

    /**
     * @param $boardId
     * @param array $paramArray
     * @return SprintSearchResult|object
     * @throws \JiraAgileRestApi\JiraException
     * @throws \JsonMapper_Exception
     */
    public function getSprints($boardId, $paramArray = [])
    {
        $ret = $this->exec($this->uri.'/'.$boardId.'/sprint'.$this->toHttpQueryParameter($paramArray), null);
        $this->log->addInfo("Result=\n".$ret);
        return $this->json_mapper->map(
            json_decode($ret), new SprintSearchResult()
        );
    }
}