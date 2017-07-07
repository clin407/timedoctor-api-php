<?php

namespace smartsites\codeception\support\tests;

use Faker\Generator;
use smartsites\codeception\support\utils\SeededFaker;

trait TestWithSeededFaker
{

    /* @var Generator */
    private $faker;

    public function _inject() {
        $this->faker = SeededFaker::create();
    }

}
