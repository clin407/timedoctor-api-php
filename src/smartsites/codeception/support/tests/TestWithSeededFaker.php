<?php

namespace smartsites\codeception\support\tests;

use Faker\Generator;
use smartsites\codeception\support\utils\SeededFaker;

/**
 * @property Generator $faker
 */
trait TestWithSeededFaker
{

    /* @var Generator */
    private $faker;

    public function _before() {
        $this->faker = SeededFaker::create();
    }

}
