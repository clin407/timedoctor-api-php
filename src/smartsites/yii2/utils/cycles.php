<?php

namespace smartsites\yii2\utils;

/**
 * Repeats a function $times times, passing current step number (starting from
 * 0) to the function.
 * @param $iterations
 * @param callable $procedure Function that accepts current
 */
function repeat($iterations, callable $procedure)
{
    for ($i = 0; $i < $iterations; $i++) {
        $procedure($i);
    }
}
