<?php

use yii\db\Migration;

/**
 * Class m180615_051858_appointment_spec
 */
class m180615_051858_appointment_spec extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn(\common\entities\OrganizationAppointment::tableName(), 'specialistId', $this->integer(11));
        $this->alterColumn(\common\entities\OrganizationAppointment::tableName(), 'userId', $this->integer(11));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(\common\entities\OrganizationAppointment::tableName(), 'specialistId', $this->integer(11)->notNull());
        $this->alterColumn(\common\entities\OrganizationAppointment::tableName(), 'userId', $this->integer(11)->notNull());

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180615_051858_appointment_spec cannot be reverted.\n";

        return false;
    }
    */
}
