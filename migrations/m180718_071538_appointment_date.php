<?php

use yii\db\Migration;

/**
 * Class m180718_071538_appointment_date
 */
class m180718_071538_appointment_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete('organization_appointment');
        $this->alterColumn('organization_appointment', 'date', $this->integer(10)->notNull());
        $this->alterColumn('organization_appointment', 'timeFrom', $this->integer(10));
        $this->alterColumn('organization_appointment', 'timeTo', $this->integer(10));
        $this->addColumn('organization_appointment', 'price', $this->float());
        $this->addColumn('organization_appointment', 'status', $this->string(255)->notNull()->defaultValue(\common\enums\OrganizationAppointmentStatus::CREATED));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('organization_appointment');
        $this->alterColumn('organization_appointment', 'date', $this->string(255)->notNull());
        $this->alterColumn('organization_appointment', 'timeFrom', $this->string(20));
        $this->alterColumn('organization_appointment', 'timeTo', $this->string(20));
        $this->dropColumn('organization_appointment', 'price');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180718_071538_appointment_date cannot be reverted.\n";

        return false;
    }
    */
}
