<?php

use common\entities\Catalog;
use common\entities\Organization;
use common\entities\OrganizationAppointment;
use common\entities\OrganizationCatalog;
use common\entities\Specialist;
use common\entities\SpecialistCatalog;
use common\entities\User;
use yii\db\Migration;

/**
 * Class m180615_040245_appointment_catalogs
 */
class m180615_040245_appointment_catalogs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn(OrganizationAppointment::tableName(), 'time');
        $this->dropColumn(OrganizationAppointment::tableName(), 'text');
        $this->addColumn(OrganizationAppointment::tableName(), 'date', $this->string(20));
        $this->addColumn(OrganizationAppointment::tableName(), 'timeFrom', $this->string(20));
        $this->addColumn(OrganizationAppointment::tableName(), 'timeTo', $this->string(20));
        $this->addColumn(OrganizationAppointment::tableName(), 'now', $this->boolean()->notNull()->defaultValue(false));
        $this->addColumn(OrganizationAppointment::tableName(), 'phone', $this->string(255)->notNull());

        $this->addColumn(User::tableName(), 'phone', $this->string(255)->notNull());


        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(OrganizationCatalog::tableName(), [
            'id' => $this->primaryKey(),
            'organizationId' => $this->integer(11)->notNull(),
            'catalogId' => $this->integer(11)->notNull(),
            'price' => $this->float()->notNull()->defaultValue(0),
            'time' => $this->integer()
        ], $tableOptions);

        $this->addForeignKey('fk_organization_catalog_organization', OrganizationCatalog::tableName(), 'organizationId', Organization::tableName(), 'id');
        $this->addForeignKey('fk_organization_catalog_catalog', OrganizationCatalog::tableName(), 'catalogId', Catalog::tableName(), 'id');

        $this->createTable(SpecialistCatalog::tableName(), [
            'id' => $this->primaryKey(),
            'specialistId' => $this->integer(11)->notNull(),
            'catalogId' => $this->integer(11)->notNull(),
            'price' => $this->float()->notNull()->defaultValue(0),
            'time' => $this->integer()
        ], $tableOptions);

        $this->addForeignKey('fk_specialist_catalog_specialist', SpecialistCatalog::tableName(), 'specialistId', Specialist::tableName(), 'id');
        $this->addForeignKey('fk_specialist_catalog_catalog', SpecialistCatalog::tableName(), 'catalogId', Catalog::tableName(), 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_specialist_catalog_catalog', SpecialistCatalog::tableName());
        $this->dropForeignKey('fk_specialist_catalog_specialist', SpecialistCatalog::tableName());
        $this->dropTable(SpecialistCatalog::tableName());

        $this->dropForeignKey('fk_organization_catalog_catalog', OrganizationCatalog::tableName());
        $this->dropForeignKey('fk_organization_catalog_organization', OrganizationCatalog::tableName());
        $this->dropTable(OrganizationCatalog::tableName());

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180615_040245_appointment_catalogs cannot be reverted.\n";

        return false;
    }
    */
}
