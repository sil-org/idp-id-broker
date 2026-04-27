<?php

use yii\db\Migration;

/**
 * Handles the creation of table `reset`.
 */
class m260427_000000_create_reset_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable(
            'reset',
            [
                'id' => 'pk',
                'uid' => 'char(32) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL',
                'user_id' => 'int(11) NOT NULL',
                'expires' => 'datetime NOT NULL',
                'created' => 'datetime NOT NULL',
            ],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $this->createIndex('reset_uid_unique', 'reset', 'uid', true);
        $this->createIndex('reset_user_id_unique', 'reset', 'user_id', true);

        $this->addForeignKey(
            'fk_reset_user_id',
            '{{reset}}',
            'user_id',
            '{{user}}',
            'id',
            'NO ACTION',
            'NO ACTION'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('reset');
    }
}
