<?php

use yii\db\Migration;

/**
 * Handles the creation of table `reset` and adds password-reset email log types.
 */
class m260422_085958_create_reset_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            'reset',
            [
                'id'           => 'pk',
                'uid'          => 'char(32) CHARACTER SET ascii COLLATE ascii_general_ci not null',
                'user_id'      => 'int(11) not null',
                'type'         => 'varchar(20) not null',
                'code'         => 'varchar(64) null',
                'attempts'     => 'int(11) not null default 0',
                'expires'      => 'datetime not null',
                'disable_until' => 'datetime null',
                'created'      => 'datetime not null',
                'email'        => 'varchar(255) null',
            ],
            "ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        $this->addForeignKey(
            'fk_reset_user_id',
            '{{reset}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );

        $this->createIndex('uq_reset_uid', '{{reset}}', 'uid', true);
        $this->createIndex('uq_reset_user_id', '{{reset}}', 'user_id', true);

        // Add password-reset message types to email_log enum
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes',"
            . "'refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed',"
            . "'mfa-enabled','mfa-disabled','method-verify','method-reminder','method-purged',"
            . "'password-expiring','password-expired','password-pwned','ext-group-sync-errors',"
            . "'abandoned-users','mfa-recovery','mfa-recovery-help',"
            . "'password-reset','password-reset-on-behalf') null"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes',"
            . "'refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed',"
            . "'mfa-enabled','mfa-disabled','method-verify','method-reminder','method-purged',"
            . "'password-expiring','password-expired','password-pwned','ext-group-sync-errors',"
            . "'abandoned-users','mfa-recovery','mfa-recovery-help') null"
        );

        $this->dropTable('reset');
    }
}
