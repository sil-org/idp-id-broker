<?php

use yii\db\Migration;

/**
 * Removes the unused `hide` flag from the `user` table.
 */
class m261124_000001_drop_hide_column_from_user_table extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('{{user}}', 'hide');
    }

    public function safeDown()
    {
        $this->addColumn('{{user}}', 'hide', "enum('no','yes') not null default 'no'");
    }
}
