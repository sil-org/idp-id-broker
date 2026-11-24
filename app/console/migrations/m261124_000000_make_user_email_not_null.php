<?php

use yii\db\Migration;

/**
 * Removes the "contingent user" concept: every user must have a primary email, and
 * users no longer expire.
 *
 * Pre-upgrade requirement: every user must already have an `email`, or this fails.
 */
class m261124_000000_make_user_email_not_null extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{user}}', 'email', 'varchar(255) not null');
        $this->dropColumn('{{user}}', 'expires_on');
    }

    public function safeDown()
    {
        $this->addColumn('{{user}}', 'expires_on', $this->date()->null()->after('personal_email'));
        $this->alterColumn('{{user}}', 'email', 'varchar(255) null');
    }
}
