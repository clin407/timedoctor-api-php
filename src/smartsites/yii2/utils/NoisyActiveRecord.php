<?php

namespace smartsites\yii2\utils;

use ErrorException;
use yii\db\ActiveRecord;

/**
 * Methods for ActiveRecord that fail with an exception when validation doesn't pass.
 */
trait NoisyActiveRecord
{

    public function noisySave() {
        /* @var ActiveRecord $this */
        $valid = $this->save();
        if (!$valid) {
            throw new ErrorException(
                "\nEntity ".$this->className().":\n"
                . print_r($this->attributes, true)
                . "\nValidation errors:\n"
                . print_r($this->errors, true)
            );
        }
    }

    /**
     * @param null $attributeNames
     * @param bool $clearErrors
     * @see ActiveRecord::validate() for parameters explanation
     * @throws ErrorException If there are any validation errors
     */
    public function noisyValidate($attributeNames = null, $clearErrors = true) {
        /* @var ActiveRecord $this */
        $valid = $this->validate($attributeNames, $clearErrors);
        if (!$valid) {
            throw new ErrorException(print_r($this->errors, true));
        }
    }

}
