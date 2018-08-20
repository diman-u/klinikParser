<?php

use common\entities\Catalog;
use common\entities\City;
use common\entities\Country;
use common\entities\Notice;
use common\entities\Organization;
use common\entities\OrganizationAppointment;
use common\entities\OrganizationLegalInfo;
use common\entities\OrganizationReview;
use common\entities\Region;
use common\entities\Specialist;
use common\entities\Note;
use common\entities\NotePhoto;
use common\entities\SpecialistReview;
use common\entities\User;
use common\entities\UserRecentOrganization;
use common\entities\UserRecentSpecialist;
use common\enums\NoticeType;
use common\enums\OrganizationStatus;
use common\enums\SpecialistStatus;
use yii\db\Migration;

class m171123_220912_base_entities extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $transaction = $this->db->beginTransaction();
        try {
            $this->createTable(Country::tableName(), [
                'id' => $this->primaryKey(),
                'title' => $this->string(255)->notNull(),
                'shortTitle' => $this->string(255),
                'engTitle' => $this->string(255),
                'titleSearch' => $this->string(255),
                'engTitleSearch' => $this->string(255)
            ], $tableOptions);

            $this->createIndex('idx_country_title', Country::tableName(), ['titleSearch']);
            $this->createIndex('idx_country_engtitle', Country::tableName(), ['engTitleSearch']);

            $this->createTable(Region::tableName(), [
                'id' => $this->primaryKey(),
                'countryId' => $this->integer(11)->notNull(),
                'title' => $this->string(255)->notNull(),
                'shortTitle' => $this->string(255),
                'engTitle' => $this->string(255),
                'titleSearch' => $this->string(255),
                'engTitleSearch' => $this->string(255)
            ], $tableOptions);

            $this->createIndex('idx_region_title', Region::tableName(), ['titleSearch']);
            $this->createIndex('idx_region_engtitle', Region::tableName(), ['engTitleSearch']);
            $this->addForeignKey('fk_region_country', Region::tableName(), 'countryId', Country::tableName(), 'id');

            $this->createTable(City::tableName(), [
                'id' => $this->primaryKey(),
                'regionId' => $this->integer(11)->notNull(),
                'countryId' => $this->integer(11)->notNull(),
                'title' => $this->string(255)->notNull(),
                'shortTitle' => $this->string(255),
                'engTitle' => $this->string(255),
                'titleSearch' => $this->string(255),
                'engTitleSearch' => $this->string(255)
            ], $tableOptions);

            $this->createIndex('idx_city_title', City::tableName(), ['titleSearch']);
            $this->createIndex('idx_city_engtitle', City::tableName(), ['engTitleSearch']);
            $this->addForeignKey('fk_city_country', City::tableName(), 'countryId', Country::tableName(), 'id');
            $this->addForeignKey('fk_city_region', City::tableName(), 'regionId', Region::tableName(), 'id');

            $this->createTable(Organization::tableName(), [
                'id' => $this->primaryKey(),
                'legalInfoId' => $this->integer(11)->notNull(),
                'cityId' => $this->integer(11)->notNull(),
                'name' => $this->string(255)->notNull(),
                'status' => 'enum("'.OrganizationStatus::ACTIVE.'", "'.OrganizationStatus::DISABLED.'", "'.OrganizationStatus::DELETED.'")',
                'address' => $this->string(255),
                'logo' => $this->string(255),
                'logoSmall' => $this->string(255),
                'phone' => $this->string(255),
                'email' => $this->string(255),
                'site' => $this->string(255),
                'schedule' => $this->string(),
                'description' => $this->string(),
                'rating' => $this->float(),
                'reviewsCount' => $this->integer()->notNull()->defaultValue(0)
            ], $tableOptions);

            $this->createIndex('idx_organization_status', Organization::tableName(), ['status', 'name', 'address']);
            $this->createIndex('idx_organization_cityId', Organization::tableName(), ['cityId', 'status', 'name', 'address']);

            $this->createTable(OrganizationLegalInfo::tableName(), [
                'id' => $this->primaryKey(),
                'legalName' => $this->string(1024)->notNull(),
                'legalAddress' => $this->string(1024),
                'inn' => $this->string(255),
                'ogrn' => $this->string(255),
                'kpp' => $this->string(255),
                'docReg' => $this->string(255),
                'docReg2' => $this->string(255),
            ], $tableOptions);

            $this->createTable(Catalog::tableName(), [
                'id' => $this->primaryKey(),
                'parentId' => $this->integer(11),
                'title' => $this->string(255)->notNull(),
                'shortTitle' => $this->string(255)->notNull(),
                'description' => $this->string(255),
                'image' => $this->string(255),
                'url' => $this->string(255),
                'level' => $this->smallInteger(),
                'priority' => $this->integer()->notNull()->defaultValue(0)
            ], $tableOptions);

            $this->createIndex('idx_catalog_parentId_title', Catalog::tableName(), ['parentId', 'title']);
            $this->addForeignKey('fk_catalog_parent', Catalog::tableName(), 'parentId', Catalog::tableName(), 'id');

            $this->createTable(Organization::catalogsViaTableName(), [
                'catalogId' => $this->integer(11)->notNull(),
                'organizationId' => $this->integer(11)->notNull()
            ], $tableOptions);

            $this->addForeignKey('fk_catalog_organization_catalog', Organization::catalogsViaTableName(), 'catalogId', Catalog::tableName(), 'id');
            $this->addForeignKey('fk_catalog_organization_organization', Organization::catalogsViaTableName(), 'organizationId', Organization::tableName(), 'id');

            $this->createTable(Specialist::catalogsViaTableName(), [
                'catalogId' => $this->integer(11)->notNull(),
                'specialistId' => $this->integer(11)->notNull()
            ], $tableOptions);

            $this->addForeignKey('fk_catalog_specialist_catalog', Specialist::catalogsViaTableName(), 'catalogId', Catalog::tableName(), 'id');
            $this->addForeignKey('fk_catalog_specialist_specialist', Specialist::catalogsViaTableName(), 'specialistId', Specialist::tableName(), 'id');

            $this->createTable(Specialist::tableName(), [
                'id' => $this->primaryKey(),
                'organizationId' => $this->integer(11)->notNull(),
                'status' => 'enum("'.SpecialistStatus::ACTIVE.'","'.SpecialistStatus::DISABLED.'","'.SpecialistStatus::DELETED.'")',
                'name' => $this->string(255)->notNull(),
                'photo' => $this->string(255),
                'photoSmall' => $this->string(255),
                'photoWorkspace' => $this->string(255),
                'photoWorkspaceSmall' => $this->string(255),
                'experience' => $this->smallInteger(),
                'profession' => $this->string(255),
                'cost' => $this->float(),
                'schedule' => $this->string(),
                'rating' => $this->float()->notNull()->defaultValue(0),
                'reviewsCount' => $this->integer()->notNull()->defaultValue(0),
                'description' => $this->string(255),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->createIndex('idx_specialist_name', Specialist::tableName(), ['status', 'name']);
            $this->createIndex('idx_specialist_org', Specialist::tableName(), ['status', 'organizationId', 'name']);
            $this->addForeignKey('fk_specialist_org', Specialist::tableName(), 'organizationId', Organization::tableName(), 'id');

            $this->createTable(OrganizationAppointment::tableName(), [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(11)->notNull(),
                'catalogId' => $this->integer(11)->notNull(),
                'specialistId' => $this->integer(11)->notNull(),
                'organizationId' => $this->integer(11)->notNull(),
                'text' => $this->string(1024)->notNull(),
                'time' => $this->string(255),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_organization_appointment_user', OrganizationAppointment::tableName(), 'userId', User::tableName(), 'id');
            $this->addForeignKey('fk_organization_appointment_catalog', OrganizationAppointment::tableName(), 'catalogId', Catalog::tableName(), 'id');
            $this->addForeignKey('fk_organization_appointment_specialist', OrganizationAppointment::tableName(), 'specialistId', Specialist::tableName(), 'id');
            $this->addForeignKey('fk_organization_appointment_organization', OrganizationAppointment::tableName(), 'organizationId', Organization::tableName(), 'id');

            $this->createTable(OrganizationReview::tableName(), [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(11)->notNull(),
                'organizationId' => $this->integer(11)->notNull(),
                'text' => $this->string(5000)->notNull(),
                'rating' => $this->smallInteger()->notNull()->defaultValue(0),
                'recommend' => $this->boolean()->notNull()->defaultValue(true),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_organization_review_user', OrganizationReview::tableName(), 'userId', User::tableName(), 'id');
            $this->addForeignKey('fk_organization_review_organization', OrganizationReview::tableName(), 'organizationId', Organization::tableName(), 'id');

            $this->createTable(SpecialistReview::tableName(), [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(11)->notNull(),
                'specialistId' => $this->integer(11)->notNull(),
                'text' => $this->string(5000)->notNull(),
                'rating' => $this->smallInteger()->notNull()->defaultValue(0),
                'recommend' => $this->boolean()->notNull()->defaultValue(true),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_specialist_review_user', SpecialistReview::tableName(), 'userId', User::tableName(), 'id');
            $this->addForeignKey('fk_specialist_review_specialist', SpecialistReview::tableName(), 'specialistId', Specialist::tableName(), 'id');

            $this->createTable(Note::tableName(), [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(11)->notNull(),
                'specialistId' => $this->integer(11)->notNull(),
                'text' => $this->string(5000)->notNull(),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_specialist_note_user', Note::tableName(), 'userId', User::tableName(), 'id');
            $this->addForeignKey('fk_specialist_note_specialist', Note::tableName(), 'specialistId', Specialist::tableName(), 'id');

            $this->createTable(NotePhoto::tableName(), [
                'id' => $this->primaryKey(),
                'specialistNoteId' => $this->integer(11)->notNull(),
                'photo' => $this->string(255)->notNull(),
                'photoSmall' => $this->string(255)->notNull(),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_specialist_note_photo_specialist_note', NotePhoto::tableName(), 'specialistNoteId', Note::tableName(), 'id');

            $this->createTable(Notice::tableName(), [
                'id' => $this->primaryKey(),
                'organizationId' => $this->integer(11),
                'userId' => $this->integer(11)->notNull(),
                'text' => $this->string(1024)->notNull(),
                'type' => $this->string(255)->notNull(),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->addForeignKey('fk_notice_organization', Notice::tableName(), 'organizationId', Organization::tableName(), 'id');
            $this->addForeignKey('fk_notice_user', Notice::tableName(), 'userId', User::tableName(), 'id');

            $this->createTable(UserRecentOrganization::tableName(), [
                'userId' => $this->integer(11)->notNull(),
                'organizationId' => $this->integer(11)->notNull(),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->createIndex('idx_uro_uniq', UserRecentOrganization::tableName(), ['organizationId', 'userId'], true);
            $this->addForeignKey('fk_uro_organization', UserRecentOrganization::tableName(), 'organizationId', Organization::tableName(), 'id');
            $this->addForeignKey('fk_uro_user', UserRecentOrganization::tableName(), 'userId', User::tableName(), 'id');

            $this->createTable(UserRecentSpecialist::tableName(), [
                'userId' => $this->integer(11)->notNull(),
                'specialistId' => $this->integer(11)->notNull(),
                'createdAt' => $this->integer()->notNull(),
            ], $tableOptions);

            $this->createIndex('idx_urs_uniq', UserRecentSpecialist::tableName(), ['specialistId', 'userId'], true);
            $this->addForeignKey('fk_urs_specialist', UserRecentSpecialist::tableName(), 'specialistId', Specialist::tableName(), 'id');
            $this->addForeignKey('fk_urs_user', UserRecentSpecialist::tableName(), 'userId', User::tableName(), 'id');

            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $transaction->rollBack();
        }
    }

    public function down()
    {
        $transaction = $this->db->beginTransaction();
        try {

            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $transaction->rollBack();
        }
    }
}
