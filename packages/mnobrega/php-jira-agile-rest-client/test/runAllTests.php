<?php
/**
 * Created by PhpStorm.
 * User: mnobrega
 * Date: 21/01/2018
 * Time: 16:25
 */

namespace JiraAgileRestApi;
use JiraAgileRestApi\BacklogIssue\BacklogIssue;
use JiraAgileRestApi\BacklogIssue\BacklogIssueService;
use JiraAgileRestApi\Board\BoardService;
use JiraAgileRestApi\Configuration\DotEnvConfiguration;
use JiraAgileRestApi\Issue\Issue;
use JiraAgileRestApi\Issue\IssueService;
use JiraAgileRestApi\IssueRank\IssueRank;
use JiraAgileRestApi\IssueRank\IssueRankService;
use JiraAgileRestApi\Sprint\Sprint;
use JiraAgileRestApi\Sprint\SprintIssue;
use JiraAgileRestApi\Sprint\SprintService;

require_once(__DIR__.'/../vendor/autoload.php');

$dotEnvConfig = new DotEnvConfiguration(__DIR__."/../");
$issueRankService = new IssueRankService($dotEnvConfig);
$issueService = new IssueService($dotEnvConfig);
$backlogIssueService = new BacklogIssueService($dotEnvConfig);
$sprintService = new SprintService($dotEnvConfig);
$boardService = new BoardService($dotEnvConfig);

$testIssueKey = 'VVESTIOS-152';
$testBeforeIssueKey = 'VVESTIOS-149';
$testBoardId=5;

try {

    // TEST get All Boards
    $boards = $boardService->getAllBoards();
    dd($boards);

    // TEST get board sprints
    $boardSprints = $boardService->getSprints($testBoardId);
    dump($boardSprints);

    // TEST change Rank
    $issueRank = new IssueRank();
    $issueRank->issues = [
        $testIssueKey
    ];
    $issueRank->rankBeforeIssue = $testBeforeIssueKey;
    $issueRankService->update($issueRank);

    // TEST get Issue
    $params = ["fields"=>"sprint"];
    $issue = $issueService->get($testIssueKey,$params);
    /** @var $issue Issue */
    echo "\nTEST get Issue\n";
    dump($issue);

    // TEST move Issues to Backlog
    $backlogIssue = new BacklogIssue();
    $backlogIssue->issues = [
        $testIssueKey
    ];
    $backlogIssueService->create($backlogIssue);

    // TEST get Sprint
    $sprint = $sprintService->get($issue->fields->sprint->id);
    echo "\nTEST get Sprint\n";
    dump($sprint);

    // TEST update Sprint
    $now = new \DateTime();
    $now->modify("-5 second");
    $sprint->name = "Modified ".date("YmdHis");
    $sprint->startDate = $now->format(JiraClient::JIRA_DATE_FORMAT);
    $now->modify("+4 second");
    $sprint->endDate = $now->format(JiraClient::JIRA_DATE_FORMAT);
    $sprintService->update($sprint->id,$sprint);
    $sprint = $sprintService->get($issue->fields->sprint->id);
    echo "\nTEST update Sprint\n";
    dump($sprint);

    // TEST create Sprint
    $now = new \DateTime();
    $newSprint = new Sprint();
    $newSprint->name = "Created ".date("YmdHis");
    $newSprint->startDate = $now->format(JiraClient::JIRA_DATE_FORMAT);
    $now->modify("+5 second");
    $newSprint->endDate = $now->format(JiraClient::JIRA_DATE_FORMAT);
    $newSprint->originBoardId = $issue->fields->sprint->originBoardId;
    $newSprint = $sprintService->create($newSprint);
    echo "\nTEST created Sprint\n";
    dump($newSprint);

    // TEST move Issue to Sprint
    $sprintIssue = new SprintIssue();
    $sprintIssue->issues = [$issue->key];
    $sprintService->addIssues($newSprint->id,$sprintIssue);

    //ROLLBACK
    $sprintService->addIssues($issue->fields->sprint->id,$sprintIssue);
    $issueRank = new IssueRank();
    $issueRank->issues = [
        $testBeforeIssueKey
    ];
    $issueRank->rankBeforeIssue = $testIssueKey;
    $issueRankService->update($issueRank);
    $sprintService->delete($newSprint->id);


} catch (\Exception $e) {
    dd($e);
}
