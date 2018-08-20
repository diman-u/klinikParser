<?php

use common\entities\Notice;
use yii\db\Migration;

/**
 * Class m180606_123314_notice_photo
 */
class m180606_123314_notice_photo extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(Notice::tableName(), 'photo', $this->string(255));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(Notice::tableName(), 'photo');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180606_123314_notice_photo cannot be reverted.\n";

        return false;
    }
    */
}
