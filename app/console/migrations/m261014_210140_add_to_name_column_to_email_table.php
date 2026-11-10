<?php

use yii\db\Migration;

/**
 * Handles adding to_name to table `email`.
 */
class m261014_210140_add_to_name_column_to_email_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{email}}', 'to_name', 'varchar(255) null after `to_address`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{email}}', 'to_name');
    }
}
