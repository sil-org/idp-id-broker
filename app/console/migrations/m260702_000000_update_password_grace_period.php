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
        // Can't safely roll back this one until m260527_000000_update_password_expiration_grace_period
        // is rolled back, need to update the expires on, then grace_period_ends_on can be updated again
    }
}
