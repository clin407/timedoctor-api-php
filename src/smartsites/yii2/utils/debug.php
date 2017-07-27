<?php

namespace smartsites\yii2\utils;

/**
 * Throws an exception with the full dump of any PHP value. Useful for
 * debugging.
 * @param mixed $value
 * @throws \ErrorException
 */
function dumpInException($value)
{
    throw new \ErrorException(var_export($value, true));
}
