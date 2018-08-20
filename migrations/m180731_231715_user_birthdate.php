<?php

use yii\db\Migration;

/**
 * Class m180731_231715_user_birthdate
 */
class m180731_231715_user_birthdate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('user', 'birthDate', $this->integer());
        $this->update('user', ['birthDate' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('user', 'birthDate', $this->dateTime());
        $this->update('user', ['birthDate' => null]);

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180731_231715_user_birthdate cannot be reverted.\n";

        return false;
    }
    */
}
