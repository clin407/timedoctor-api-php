<?php

namespace Smartsites\timeDoctor;

use DateTimeZone;

/**
 * Handles situations when TD is down.
 */
class RobustTimeDoctor extends TimeDoctor
{

    public static $isTdDown = false;

    /** @var TdStatusIndicator */
    private $indicator;

    public function __construct(
        TdTokensService $tokensService,
        TdStatusIndicator $indicator,
        DateTimeZone $timezone
    )
    {
        parent::__construct($tokensService, $timezone);
        $this->indicator = $indicator;
    }

    protected function handle(callable $action)
    {
        try {
            $answer = $action();
            $this->indicator->saveTdStatus(false);
            return $answer;
        } catch (TdDownException $e) {
            $this->indicator->saveTdStatus(true);
            throw $e;
        }
    }

    public function getFullWorklog($companyId, array $userIds, $start, $end)
    {
        return $this->handle(
            function() use ($companyId, $userIds, $start, $end) {
                return parent::getFullWorklog($companyId, $userIds, $start, $end);
            }
        );
    }

    public function getUserProjects($companyId, $userId)
    {
        return $this->handle(
            function() use ($companyId, $userId) {
                return parent::getUserProjects($companyId, $userId);
            }
        );
    }

    public function getUser($companyId, $userId)
    {
        return $this->handle(
            function() use ($companyId, $userId) {
                return parent::getUser($companyId, $userId);
            }
        );
    }

    public function createProject(
        $companyId,
        $userId,
        $assignedUserIds,
        $projectName
    )
    {
        return $this->handle(
            function() use (
                $companyId,
                $userId,
                $assignedUserIds,
                $projectName
            ) {
                return parent::createProject($companyId, $userId, $assignedUserIds, $projectName);
            }
        );
    }

    public function getUsers($companyId)
    {
        return $this->handle(
            function() use ($companyId) {
                return parent::getUsers($companyId);
            }
        );
    }

}