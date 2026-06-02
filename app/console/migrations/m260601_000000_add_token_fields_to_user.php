<?php

use yii\db\Migration;

/**
 * Class m260601_000000_add_token_fields_to_user
 */
class m260601_000000_add_token_fields_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'token_hash', 'char(64) character set ascii collate ascii_general_ci after `deactivated_utc`');
        $this->addColumn('{{user}}', 'token_expiry_utc', 'datetime null after `token_hash`');
        $this->addColumn('{{user}}', 'token_type', "enum('login','reset') null after `token_expiry_utc`");
        $this->createIndex('idx_user_token_hash_expiry', '{{user}}', ['token_hash', 'token_expiry_utc']);
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'token_type');
        $this->dropColumn('{{user}}', 'token_expiry_utc');
        $this->dropColumn('{{user}}', 'token_hash');
    }
}
