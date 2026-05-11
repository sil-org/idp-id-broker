<?php

use yii\db\Migration;

class m260511_000000_change_email_log_type_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->alterColumn(
            '{{email_log}}',
            'message_type',
            "varchar(32) null"
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
            "enum('invite','welcome','mfa-rate-limit','password-changed','get-backup-codes','refresh-backup-codes','lost-security-key','mfa-option-added','mfa-option-removed','mfa-enabled','mfa-disabled','method-verify','mfa-manager','mfa-manager-help','method-reminder','method-purged','password-expiring','password-expired','password-pwned','ext-group-sync-errors','abandoned-users', 'mfa-recovery', 'mfa-recovery-help') null"
        );
    }
}
