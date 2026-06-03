<?php

use yii\db\Migration;
use yii\db\Expression;

class m260527_000000_update_password_expiration_grace_period extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $interval = $this->mysqlInterval(\Yii::$app->params['passwordMfaLifespanExtension']);

        $sql = "
            UPDATE password
            SET expires_on = expires_on {$interval}
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
        $interval = $this->mysqlInterval(\Yii::$app->params['passwordMfaLifespanExtension']);
        $interval = $this->invertIntervalSql($interval);

        $sql = "
            UPDATE password
            SET expires_on = expires_on {$interval}
            WHERE
                password.hibp_is_pwned = 'no'
                AND EXISTS (SELECT 1 FROM mfa WHERE mfa.user_id = password.user_id AND mfa.verified = 1)
        ";
        $this->execute($sql);
    }

    /**
     * Convert a simple PHP relative string like "+1 year" or "-2 months" to a MySQL interval fragment:
     * returns e.g. "+ INTERVAL 1 YEAR" or "- INTERVAL 2 MONTH", or null if it can't parse.
     *
     * Supports: seconds/minutes/hours/days/weeks/months/years (singular/plural/short forms).
     */
    protected function mysqlInterval(string $s): ?string
    {
        $s = trim($s);
        if (!preg_match('/^([+-]?\d+)\s*([a-zA-Z]+)$/', $s, $m)) {
            return null;
        }
        $n = (int)$m[1];
        $unit = strtolower($m[2]);

        $map = [
            's' => 'SECOND', 'sec' => 'SECOND', 'second' => 'SECOND', 'seconds' => 'SECOND',
            'm' => 'MINUTE', 'min' => 'MINUTE', 'minute' => 'MINUTE', 'minutes' => 'MINUTE',
            'h' => 'HOUR', 'hour' => 'HOUR', 'hours' => 'HOUR',
            'd' => 'DAY', 'day' => 'DAY', 'days' => 'DAY',
            'w' => 'WEEK', 'week' => 'WEEK', 'weeks' => 'WEEK',
            'month' => 'MONTH', 'months' => 'MONTH',
            'y' => 'YEAR', 'yr' => 'YEAR', 'year' => 'YEAR', 'years' => 'YEAR',
        ];

        if (!isset($map[$unit])) {
            return null;
        }

        $mysqlUnit = $map[$unit];
        $sign = $n < 0 ? '-' : '+';
        return sprintf("%s INTERVAL %d %s", $sign, abs($n), $mysqlUnit);
    }

    /**
     * Invert a fragment like "+ INTERVAL 1 YEAR" -> "- INTERVAL 1 YEAR"
     */
    protected function invertIntervalSql(?string $fragment): ?string
    {
        if ($fragment === null) {
            return null;
        }
        if (preg_match('/^([+-])\s+INTERVAL\s+(\d+)\s+([A-Z]+)$/', $fragment, $m)) {
            $op = $m[1] === '+' ? '-' : '+';
            return sprintf("%s INTERVAL %s %s", $op, $m[2], $m[3]);
        }
        return null;
    }
}
