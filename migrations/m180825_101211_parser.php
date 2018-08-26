<?php

use yii\db\Migration;

/**
 * Class m180825_101211_parser
 */
class m180825_101211_parser extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('organization', 'isParsed', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('organization', 'parserLink', $this->string(1024));
        $this->addColumn('specialist', 'isParsed', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn('specialist', 'parserLink', $this->string(1024));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('specialist', 'parserLink');
        $this->dropColumn('specialist', 'isParsed');
        $this->dropColumn('organization', 'parserLink');
        $this->dropColumn('organization', 'isParsed');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180825_101211_parser cannot be reverted.\n";

        return false;
    }
    */
}
