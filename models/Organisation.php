<?php

namespace console\models;

use yii\db\ActiveRecord;

class Organization extends ActiveRecord
{
    public static function tableName()
    {
        return 'organization';
    }
}