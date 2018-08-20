<?php

use yii\db\Migration;

/**
 * Class m180708_141701_notes
 */
class m180708_141701_notes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->renameTable('specialist_note', 'note');
        $this->renameTable('specialist_note_photo', 'note_photo');
        $this->dropForeignKey('fk_specialist_note_photo_specialist_note', 'note_photo');
        $this->renameColumn('note_photo', 'specialistNoteId', 'noteId');
        $this->addColumn('note', 'organizationId', $this->integer(11));
        $this->alterColumn('note', 'specialistId', $this->integer(11));
        $this->addForeignKey('fk_note_photo_note', 'note_photo', 'noteId', 'note', 'id');
        $this->addForeignKey('fk_note_organization', 'note', 'organizationId', 'organization', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_note_organization', 'note');
        $this->dropForeignKey('fk_note_photo_note', 'note_photo');
        $this->alterColumn('note', 'specialistId', $this->integer(11)->notNull());
        $this->dropColumn('note', 'organizationId');
        $this->renameColumn('note_photo', 'noteId', 'specialistNoteId');
        $this->renameTable('note', 'specialist_note');
        $this->renameTable('note_photo', 'specialist_note_photo');
        $this->addForeignKey('fk_specialist_note_photo_specialist_note', 'specialist_note_photo', 'specialistNoteId', 'specialist_note', 'id');

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180708_141701_notes cannot be reverted.\n";

        return false;
    }
    */
}
