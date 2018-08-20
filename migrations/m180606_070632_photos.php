<?php

use common\entities\Organization;
use common\entities\OrganizationPhoto;
use common\entities\Specialist;
use common\entities\SpecialistPhoto;
use yii\db\Migration;

/**
 * Class m180606_070632_photos
 */
class m180606_070632_photos extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(OrganizationPhoto::tableName(), [
            'id' => $this->primaryKey(),
            'organizationId' => $this->integer(11),
            'photo' => $this->string(255)->notNull(),
            'photoSmall' => $this->string(255)->notNull(),
            'createdAt' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk_organization_photo_organization', OrganizationPhoto::tableName(), 'organizationId', Organization::tableName(), 'id');

        $this->createTable(SpecialistPhoto::tableName(), [
            'id' => $this->primaryKey(),
            'specialistId' => $this->integer(11),
            'photo' => $this->string(255)->notNull(),
            'photoSmall' => $this->string(255)->notNull(),
            'createdAt' => $this->integer(),
        ], $tableOptions);

        $this->addForeignKey('fk_specialist_photo_specialis', SpecialistPhoto::tableName(), 'specialistId', Specialist::tableName(), 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_specialist_photo_specialis', SpecialistPhoto::tableName());
        $this->dropTable(SpecialistPhoto::tableName());
        $this->dropForeignKey('fk_organization_photo_organization', OrganizationPhoto::tableName());
        $this->dropTable(OrganizationPhoto::tableName());

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180606_070632_photos cannot be reverted.\n";

        return false;
    }
    */
}
