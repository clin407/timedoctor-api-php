<?php

namespace smartsites\yii2\utils;

/**
 * Active Record that can set some of its own attributes before validation.
 *
 * The purpose of this class is to keep all the logic related to computing such attributes in the model class. If
 * these attributes weren't automatically set before validation, they'd have to be set manually and identically in
 * multiple clients of this model.
 */
trait SelfComputingActiveRecord
{

    public function beforeValidate()
    {
        /* @var \yii\db\ActiveRecord $this */
        $this->autoSetUndefinedComputableFields();
        return parent::beforeValidate();
    }

    /**
     * @return void
     */
    public abstract function autoSetUndefinedComputableFields();

}
