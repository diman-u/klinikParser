<?php

use common\entities\User;
use common\entities\UserPointsOperation;
use yii\db\Migration;

/**
 * Class m180707_041318_user_points
 */
class m180707_041318_user_points extends Migration
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

        $this->addColumn(User::tableName(), 'points', $this->integer()->notNull()->defaultValue(0));

        $this->createTable(UserPointsOperation::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer(11)->notNull(),
            'amount' => $this->integer()->notNull(),
            'type' => $this->string(255)->notNull(),
            'reason' => $this->string(255)->notNull(),
            'createdAt' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk_user_points_operation_user', UserPointsOperation::tableName(), 'userId', User::tableName(), 'id');

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(User::tableName(), 'points');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180707_041318_user_points cannot be reverted.\n";

        return false;
    }
    */
}
