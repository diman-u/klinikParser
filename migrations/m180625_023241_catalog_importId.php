<?php

use yii\db\Migration;

/**
 * Class m180625_023241_catalog_importId
 */
class m180625_023241_catalog_importId extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(\common\entities\Catalog::tableName(), 'importId', $this->integer(11));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn(\common\entities\Catalog::tableName(), 'importId');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180625_023241_catalog_importId cannot be reverted.\n";

        return false;
    }
    */
}
