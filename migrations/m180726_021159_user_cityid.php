<?php

use yii\db\Migration;

/**
 * Class m180726_021159_user_cityid
 */
class m180726_021159_user_cityid extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'cityId', $this->integer(11)->after('organizationId'));
        $this->addForeignKey('fk_user_city', 'user', 'cityId', 'city', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_user_city', 'user');
        $this->dropColumn('user', 'cityId');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180726_021159_user_cityid cannot be reverted.\n";

        return false;
    }
    */
}
