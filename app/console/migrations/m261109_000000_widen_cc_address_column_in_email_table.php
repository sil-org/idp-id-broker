<?php

use yii\db\Migration;

/**
 * Widens `email.cc_address`, which can hold a comma-separated list (e.g. an
 * invite CCs the personal email plus all of the account's mail admins) that
 * can exceed 255 characters.
 */
class m261109_000000_widen_cc_address_column_in_email_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('{{email}}', 'cc_address', 'text null');
    }

    /**
     * {@inheritdoc}
     *
     * Note: in strict SQL mode this fails ("Data too long") if any queued email
     * still has a `cc_address` over 255 characters. Rows are removed once sent,
     * so let the queue drain (or deal with those rows) before rolling back.
     */
    public function safeDown()
    {
        $this->alterColumn('{{email}}', 'cc_address', 'varchar(255) null');
    }
}
