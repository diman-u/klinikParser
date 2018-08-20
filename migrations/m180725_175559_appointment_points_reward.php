<?php

use yii\db\Migration;

/**
 * Class m180725_175559_appointment_points_reward
 */
class m180725_175559_appointment_points_reward extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('organization_appointment', 'isAwarded', $this->boolean()->notNull()->defaultValue(false)->after('date'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('organization_appointment', 'isAwarded');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180725_175559_appointment_points_reward cannot be reverted.\n";

        return false;
    }
    */
}
