<?php

use yii\db\Migration;

class m171205_003800_access_token_entity extends Migration
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
            $this->createTable('access_token', [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(11),
                'token' => $this->string(255)->notNull(),
                'socialToken' => $this->string(255),
                'createdAt' => $this->integer(),
                'updatedAt' => $this->integer(),
                'expiredAt' => $this->integer(),
            ], $tableOptions);
            $this->addForeignKey('fk_access_token_user', 'access_token', 'userId', 'user', 'id');

            $this->addColumn('user', 'birthDate', $this->dateTime());
            $this->addColumn('user', 'sex', $this->string(255)->notNull()->defaultValue('man'));
            $this->addColumn('user', 'firstName', $this->string(255));
            $this->addColumn('user', 'firstNameEng', $this->string(255));
            $this->addColumn('user', 'lastName', $this->string(255));
            $this->addColumn('user', 'lastNameEng', $this->string(255));
            $this->addColumn('user', 'avatar', $this->string(255));
            $this->addColumn('user', 'vkUid', $this->string(255));
            $this->addColumn('user', 'fbUid', $this->string(255));
            $this->addColumn('user', 'instUid', $this->string(255));
            $this->addColumn('user', 'deletedAt', $this->integer());
            $this->addColumn('user', 'recoveryAt', $this->integer());
            $this->addColumn('user', 'isDeleted', $this->boolean()->notNull()->defaultValue(false));
            $this->addColumn('user', 'authMethod', $this->string(255));
            $this->addColumn('user', 'cityString', $this->string(255));
            $this->addColumn('user', 'countryString', $this->string(255));
            $this->addColumn('user', 'organizationId', $this->integer(11));
            $this->addColumn('user', 'displaySocialNetworkLink', $this->boolean()->notNull()->defaultValue(false));
            $this->alterColumn('user', 'status', $this->string(255)->notNull()->defaultValue(\common\enums\UserStatus::ACTIVE));

            $this->addForeignKey('fk_user_organization', \common\entities\User::tableName(), 'organizationId', \common\entities\Organization::tableName(), 'id');

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
            $this->dropForeignKey('fk_access_token_user', 'access_token');
            $this->dropTable('access_token');

            $this->dropColumn('user', 'birthDate');
            $this->dropColumn('user', 'sex');
            $this->dropColumn('user', 'firstName');
            $this->dropColumn('user', 'firstNameEng');
            $this->dropColumn('user', 'lastName');
            $this->dropColumn('user', 'lastNameEng');
            $this->dropColumn('user', 'avatar');
            $this->dropColumn('user', 'vkUid');
            $this->dropColumn('user', 'fbUid');
            $this->dropColumn('user', 'instUid');
            $this->dropColumn('user', 'deletedAt');
            $this->dropColumn('user', 'recoveryAt');
            $this->dropColumn('user', 'isDeleted');
            $this->dropColumn('user', 'authMethod');
            $this->dropColumn('user', 'cityString');
            $this->dropColumn('user', 'countryString');
            $this->dropColumn('user', 'organizationId');
            $this->dropColumn('user', 'displaySocialNetworkLink');
            $this->alterColumn('user', 'status', $this->smallInteger(6)->notNull()->defaultValue(10));

            $transaction->commit();
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $transaction->rollBack();
        }
    }
}
