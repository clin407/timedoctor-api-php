<?php

namespace Smartsites\timeDoctor;

use Curl\Curl;
use DateTimeZone;
use ErrorException;
use function Functional\flat_map;
use function Functional\map;

/**
 * Implements authorization flow and working with API explained in
 * https://webapi.timedoctor.com/doc
 */
class TimeDoctor
{

    private static $WEBAPI_URL = "https://webapi.timedoctor.com/v1.1";

    /**
     * @var int
     * @see https://webapi.timedoctor.com/doc#worklogs#get--v1.1-companies-{company_id}-worklogs
     * Open link "Returns a collection of all user's worklogs under the given
     * company Id in the URI.", Ctrl+f 500
     */
    private static $WORKLOG_REQUEST_ITEMS_LIMIT = 500;

    /** @var DateTimeZone */
    public $timezone;

    /** @var string */
    private $accessToken;

    public function __construct(
        TdTokensService $tokensService,
        DateTimeZone $timezone
    )
    {
        $this->accessToken = $tokensService->getAccessToken();
        $this->timezone = $timezone;
    }

    /**
     * @param int $companyId
     * @param int[] $userIds
     * @param string $start Y-m-d
     * @param string $end Y-m-d
     * @return TdTimeShard[]
     */
    public function getFullWorklog($companyId, array $userIds, $start, $end)
    {
        $firstPage = $this->get(
            $this->worklogUrl($companyId, $userIds, $start, $end, 0)
        );
        /** @var object[] $pages */
        $pages = [];
        $pages[] = $firstPage;
        if ($firstPage->worklogs->count > self::$WORKLOG_REQUEST_ITEMS_LIMIT) {
            $i = 1;
            do {
                $offset = self::$WORKLOG_REQUEST_ITEMS_LIMIT * $i;
                $pages[] = $this->get(
                    $this->worklogUrl(
                        $companyId, $userIds, $start, $end, $offset
                    )
                );
                $i++;
            } while ($offset < $firstPage->worklogs->count);
        }
        return map(
            flat_map(
                $pages,
                function($page) {
                    return $page->worklogs->items;
                }
            ),
            function($item) {
                return new TdTimeShard($item, $this->timezone);
            }
        );
    }

    /**
     * @param int $companyId
     * @param int[] $userIds
     * @param string $start Y-m-d
     * @param string $end Y-m-d
     * @param $offset
     * @return string
     */
    private function worklogUrl($companyId, $userIds, $start, $end, $offset)
    {
        return "/companies/"
            . $companyId
            . "/worklogs?start_date="
            . $start
            . "&end_date="
            . $end
            . "&user_ids="
            . join(",", $userIds)
            . "&offset="
            . $offset
            . "&consolidated=0";
    }

    public function getUserProjects($companyId, $userId)
    {
        return $this->getAllPages(
            "/companies/"
            . $companyId
            . "/users/"
            . $userId
            . "/projects?all=true",
            function($response) {
                return $response->projects;
            }
        );
    }

    private function getAllPages(
        $url,
        callable $getPayload,
        $data = [],
        $limit = 100
    )
    {
        $data = array_merge(
            $data,
            [
                'offset' => 0,
                'limit' => $limit
            ]
        );
        $result = [];
        do {
            $response = $this->get($url, $data);
            $result = array_merge(
                $getPayload($response),
                $result
            );
            $data['offset'] += $limit;
        } while ($data['offset'] < $response->count);
        return $result;
    }

    public function getUser($companyId, $userId)
    {
        return $this->get(
            "/companies/"
            . $companyId
            . "/users/"
            . $userId
        );
    }

    /**
     * @param $url
     * @param array $data
     * @return mixed
     */
    private function get($url, $data = [])
    {
        $curl = $this->getCurl();
        $curl->setHeader("Authorization", "Bearer " . $this->accessToken);
        $response = $curl->get(self::$WEBAPI_URL . $url, $data);
        if (isset($response->error) && $response->error === 'invalid_grant') {
            throw new ErrorException(
                "Token " . $this->accessToken . " has expired or was never valid"
            );
        }
        if ($curl->httpStatusCode !== 200) {
            throw new TdDownException();
        }
        return $response;
    }

    /**
     * @param int $companyId
     * @param int $userId
     * @param int[] $assignedUserIds
     * @param string $projectName
     * @return mixed
     * @throws ErrorException
     */
    public function createProject(
        $companyId,
        $userId,
        $assignedUserIds,
        $projectName
    )
    {
        return $this->post(
            "/companies/"
            . $companyId
            . "/users/"
            . $userId
            . "/projects"
            . "?assigned_users="
            . join(',', $assignedUserIds),
            [
                'project' => [
                    'project_name' => $projectName
                ]
            ]
        );
    }

    /**
     * @param string $url
     * @param array $data
     * @return mixed
     */
    private function post($url, $data)
    {
        $curl = $this->getCurl();
        $curl->setHeader("Authorization", "Bearer " . $this->accessToken);
        $response = $curl->post(
            self::$WEBAPI_URL . $url,
            $data
        );
        if (isset($response->error) && $response->error === 'invalid_grant') {
            throw new ErrorException(
                "Token " . $this->accessToken . " has expired or was never valid"
            );
        }
        if ($curl->httpStatusCode !== 200) {
            throw new TdDownException();
        }
        return $response;
    }

    public function getUsers($companyId)
    {
        return $this->get("/companies/" . $companyId . "/users");
    }

    /**
     * @return Curl
     */
    protected function getCurl()
    {
        return new Curl();
    }

}