<?php

namespace smartsites\yii2\utils;

trait ClassWithName
{

    public static function className() {
        return get_called_class();
    }

}
