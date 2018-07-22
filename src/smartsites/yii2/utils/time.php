<?php

namespace smartsites\yii2\utils;

/**
 * @param int $seconds
 * @return int Hours
 */
function hoursFromSeconds($seconds) {
    return intval(($seconds -$seconds% 3600) / 3600);
}

/**
 * @param int $seconds
 * @return int Minutes
 */

function minutesFromSeconds($seconds) {
    return intval(($seconds - $seconds % 60) / 60);
}
