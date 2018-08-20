<?php

use yii\db\Migration;

/**
 * Class m180615_065354_reviews_delete
 */
class m180615_065354_reviews_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete(\common\entities\OrganizationReview::tableName());
        $this->delete(\common\entities\SpecialistReview::tableName());
        $this->update(\common\entities\Organization::tableName(), ['reviewsCount' => 0]);
        $this->update(\common\entities\Specialist::tableName(), ['reviewsCount' => 0]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180615_065354_reviews_delete cannot be reverted.\n";

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180615_065354_reviews_delete cannot be reverted.\n";

        return false;
    }
    */
}
