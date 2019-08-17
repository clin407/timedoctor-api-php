<?php

namespace Smartsites\timeDoctor;

/**
 * Works along with [[RobustTimeDoctor]] to handle situations when TD is down.
 */
class TdStatusIndicator
{

    protected $isTdDown = false;

    protected $statusFilePath;

    public function __construct($statusFilePath)
    {
        $this->statusFilePath = $statusFilePath;
        if (!is_file($this->statusFilePath)) {
            self::saveTdStatus(false);
        }
        $isDown = file_get_contents(
            $this->statusFilePath
        );
        $this->isTdDown = $isDown === '1' ? true : false;
    }

    /**
     * @param boolean $isDown
     */
    public function saveTdStatus($isDown)
    {
        file_put_contents(
            $this->statusFilePath,
            $isDown ? "1" : "0"
        );
        $this->isTdDown = $isDown;
    }

}