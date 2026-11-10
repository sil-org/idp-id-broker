<?php

use yii\db\Migration;

/**
 * Class m261110_000000_increase_user_groups_length
 */
class m261110_000000_increase_user_groups_length extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{user}}', '{{groups}}', 'varchar(1024) null');
    }

    public function safeDown()
    {
        $this->alterColumn('{{user}}', '{{groups}}', 'varchar(255) null');
    }
}
