<?php

use common\helpers\MySqlDateTime;
use yii\db\Migration;
use yii\db\Expression;

class m260702_000000_update_password_grace_period extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $interval = MySqlDateTime::interval(\Yii::$app->params['passwordGracePeriodExtension']);

        $sql = "
            UPDATE password
            SET grace_period_ends_on = expires_on {$interval}
            WHERE
                password.hibp_is_pwned = 'no'
                AND EXISTS (SELECT 1 FROM mfa WHERE mfa.user_id = password.user_id AND mfa.verified = 1)
        ";
        $this->execute($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $interval = MySqlDateTime::interval(\Yii::$app->params['passwordGracePeriodExtension']);
        $interval = MySqlDateTime::invertInterval($interval);

        $sql = "
            UPDATE password
            SET grace_period_ends_on = expires_on {$interval}
            WHERE
                password.hibp_is_pwned = 'no'
                AND EXISTS (SELECT 1 FROM mfa WHERE mfa.user_id = password.user_id AND mfa.verified = 1)
        ";
        $this->execute($sql);
    }
}
