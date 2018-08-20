<?php

use yii\db\Migration;

/**
 * Class m180803_222022_specialist_schedule
 */
class m180803_222022_specialist_schedule extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('specialist_schedule_item', [
            'id' => $this->primaryKey(),
            'specialistId' => $this->integer(11)->notNull(),
            'date' => $this->integer()->notNull(),
            'timeFrom' => $this->integer(),
            'timeTo' => $this->integer(),
            'isTaken' => $this->boolean()->notNull()->defaultValue(false)
        ], $tableOptions);

        $this->addForeignKey('fk_specialist_schedule_item_specialist', 'specialist_schedule_item', 'specialistId', 'specialist', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_specialist_schedule_item_specialist', 'specialist_schedule_item');
        $this->dropTable('specialist_schedule_item');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180803_222022_specialist_schedule cannot be reverted.\n";

        return false;
    }
    */
}
