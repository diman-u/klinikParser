<?php

use yii\db\Migration;

/**
 * Class m180615_155447_favorites
 */
class m180615_155447_favorites extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(\common\entities\UserRecentOrganization::tableName(), 'isFavorite', $this->boolean()->notNull()->defaultValue(false)->after('organizationId'));
        $this->addColumn(\common\entities\UserRecentSpecialist::tableName(), 'isFavorite', $this->boolean()->notNull()->defaultValue(false)->after('specialistId'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(\common\entities\UserRecentOrganization::tableName(), 'isFavorite');
        $this->dropColumn(\common\entities\UserRecentSpecialist::tableName(), 'isFavorite');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180615_155447_favorites cannot be reverted.\n";

        return false;
    }
    */
}
