<?php

use yii\db\Migration;

/**
 * Class m180726_045554_notice_group_id
 */
class m180726_045554_notice_group_id extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('notice', 'groupId', $this->integer());
        $this->createIndex('idx_notice_group', 'notice', 'groupId');
        $this->createIndex('idx_notice_type_group', 'notice', ['type', 'groupId']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_notice_type_group', 'notice');
        $this->dropIndex('idx_notice_group', 'notice');
        $this->dropColumn('notice', 'groupId');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180726_045554_notice_group_id cannot be reverted.\n";

        return false;
    }
    */
}
