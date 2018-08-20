<?php

namespace console\models;

use yii\db\ActiveRecord;

class Parser extends ActiveRecord
{
    public $table;

    public static function tableName()
    {
        return 'specialist';
    }
}