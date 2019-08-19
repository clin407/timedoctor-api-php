<?php

namespace Smartsites\tdAcBridge;

use Carbon\Carbon;
use DateTimeZone;

/**
 * A worklog item in TD.
 *
 * A time range in TD when a developer has continuously been working on a single task.
 */
class TdTimeShard
{

    /** @var Carbon */
    public $startTime;

    /** @var string */
    public $taskName;

    /** @var string */
    public $projectName;

    /** @var object */
    private $timeRecord;

    /**
     * @param object $timeRecord Returned by [[TimeDoctor::getFullWorklog()]
     * @param DateTimeZone $timezone TD timezone
     */
    public function __construct($timeRecord, DateTimeZone $timezone)
    {
        $this->timeRecord = $timeRecord;
        $this->startTime = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $timeRecord->start_time,
            $timezone
        );
        $this->taskName = html_entity_decode($timeRecord->task_name);
        $this->projectName = html_entity_decode($timeRecord->project_name);
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->timeRecord->user_id;
    }

    /**
     * @return int
     */
    public function getUserName()
    {
        return $this->timeRecord->user_name;
    }

    /**
     * @return int
     */
    public function getSecondsWorked()
    {
        return $this->timeRecord->length;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->timeRecord->id;
    }

    /**
     * @return int
     */
    public function getTaskId()
    {
        return $this->timeRecord->task_id;
    }

}