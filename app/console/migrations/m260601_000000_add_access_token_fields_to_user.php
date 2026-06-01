<?php

use yii\db\Migration;

/**
 * Class m260601_000000_add_access_token_fields_to_user
 */
class m260601_000000_add_access_token_fields_to_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{user}}', 'access_token', 'varchar(255) null after `deactivated_utc`');
        $this->addColumn('{{user}}', 'access_token_expiration', 'datetime null after `access_token`');
        $this->addColumn('{{user}}', 'auth_type', "enum('login','reset') null after `access_token_expiration`");
    }

    public function safeDown()
    {
        $this->dropColumn('{{user}}', 'auth_type');
        $this->dropColumn('{{user}}', 'access_token_expiration');
        $this->dropColumn('{{user}}', 'access_token');
    }
}
