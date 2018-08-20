<?php

use yii\db\Migration;

/**
 * Class m180716_234913_organization_fields
 */
class m180716_234913_organization_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('organization', 'tariff', $this->smallInteger());
        $this->addColumn('organization', 'adminPhone', $this->string(255));
        $this->addColumn('organization', 'groupVk', $this->string(255));
        $this->addColumn('organization', 'groupFb', $this->string(255));
        $this->addColumn('organization', 'groupInst', $this->string(255));
        $this->alterColumn('organization', 'schedule', $this->string(1000));
        $this->alterColumn('specialist', 'schedule', $this->string(5000));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('organization', 'tariff');
        $this->dropColumn('organization', 'adminPhone');
        $this->dropColumn('organization', 'groupVk');
        $this->dropColumn('organization', 'groupFb');
        $this->dropColumn('organization', 'groupInst');
        $this->alterColumn('organization', 'schedule', $this->string(255));
        $this->alterColumn('specialist', 'schedule', $this->string(255));

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180716_234913_organization_fields cannot be reverted.\n";

        return false;
    }
    */
}
