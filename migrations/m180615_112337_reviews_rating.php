<?php

use yii\db\Migration;

/**
 * Class m180615_112337_reviews_rating
 */
class m180615_112337_reviews_rating extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->delete(\common\entities\OrganizationReview::tableName());
        $this->addColumn(\common\entities\Organization::tableName(), 'sumRating', $this->float()->notNull()->defaultValue(0));
        $this->update(\common\entities\Organization::tableName(), ['reviewsCount' => 0, 'rating' => 0]);

        $this->delete(\common\entities\SpecialistReview::tableName());
        $this->addColumn(\common\entities\Specialist::tableName(), 'sumRating', $this->float()->notNull()->defaultValue(0));
        $this->update(\common\entities\Specialist::tableName(), ['reviewsCount' => 0, 'rating' => 0]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(\common\entities\OrganizationReview::tableName(), 'sumRating');
        $this->dropColumn(\common\entities\Specialist::tableName(), 'sumRating');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180615_112337_reviews_rating cannot be reverted.\n";

        return false;
    }
    */
}
