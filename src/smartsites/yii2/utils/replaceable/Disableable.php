<?php

namespace smartsites\yii2\utils;

use yii\db\ActiveRecordInterface;

/**
 * Entities in the database that can be "disabled".
 * @property int $enabled
 */
interface Disableable extends ActiveRecordInterface
{

}
