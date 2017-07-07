<?php

namespace smartsites\codeception\support\utils;

use Faker\Generator;

class SeededFaker
{
    /**
     * Creates \Faker\Generator seeded with 0 to use in tests.
     * @return Generator
     */
    public static function create()
    {
        $generator = \Faker\Factory::create();
        $generator->seed(0);
        return $generator;
    }
}
