<?php

namespace Smartsites\timeDoctor;

use Carbon\Carbon;
use Smartsites\tdAcBridge\TdTimeShard;
use function Functional\filter;

/**
 * A company on TimeDoctor
 */
class TdCompany
{

    /** @var TimeDoctor */
    protected $td;

    /** @var int */
    protected $companyId;

    public function __construct(TimeDoctor $td, $companyId)
    {
        $this->td = $td;
        $this->companyId = $companyId;
    }

    /**
     * @param int[] $userIds
     * @param Carbon $start Start second
     * @param Carbon $end End second, inclusive
     * @return TdTimeShard[]
     */
    public function getFullWorklogForLocalTime(
        array $userIds,
        Carbon $start,
        Carbon $end
    )
    {
        return filter(
            $this->td->getFullWorklog(
                $this->companyId,
                $userIds,
                $start->copy()->setTimezone($this->td->timezone)->toDateString(),
                $end->copy()->setTimezone($this->td->timezone)->toDateString()
            ),
            function($timeRecord) use ($end, $start) {
                $time = Carbon::createFromFormat(Carbon::DEFAULT_TO_STRING_FORMAT, $timeRecord->end_time, $this->td->timezone);
                return $time->greaterThanOrEqualTo($start)
                    && $time->lessThanOrEqualTo($end);
            }
        );
    }

    /**
     * @param int[] $userIds
     * @param string $start Y-m-d
     * @param string $end Y-m-d
     * @return TdTimeShard[]
     */
    public function getFullWorklog(array $userIds, $start, $end)
    {
        return $this->td->getFullWorklog($this->companyId, $userIds, $start, $end);
    }

    public function getUserProjects($userId)
    {
        return $this->td->getUserProjects($this->companyId, $userId);
    }

    public function getUser($userId) {
        return $this->td->getUser($this->companyId, $userId);
    }

}