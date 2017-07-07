<?php

namespace smartsites\codeception\modules;

use Codeception\Module;
use Yii;

class MailHelper extends Module
{

    public function cleanMail() {
        $this->getModule("Filesystem")->deleteDir(Yii::getAlias("@tests/output/mail"));
    }

    public function countSentEmails($number) {
        $this->assertEquals(
            $number,
            count(
                glob(Yii::getAlias("@tests/output/mail") . '/*.eml')
            )
        );
    }

}
