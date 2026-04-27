<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class Reset
 * @package common\models
 */
class Reset extends ResetBase
{
    const int CODE_LENGTH = 6;
    const string LIFETIME = '60 minutes';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],
                [
                    ['created'], 'default', 'value' => MySqlDateTime::now(),
                ],
                [
                    ['expires'], 'default', 'value' => self::calculateExpireTime(),
                ],
                [
                    ['type'], 'default', 'value' => self::TYPE_PRIMARY,
                ],
                [
                    ['attempts'], 'default', 'value' => 0,
                ],
                [
                    ['code'], 'default', 'value' => self::generateCode(),
                ],
                [
                    ['email'], 'email',
                ],
            ],
            parent::rules()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * Create a reset for the given user. If one exists, it will be reused.
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    public static function create(User $user): void
    {
        if ($user->isLocked()) {
            return;
        }

        $reset = self::findOne(['user_id' => $user->id]);

        if ($reset === null) {
            $reset = new Reset();
            $reset->user_id = $user->id;

            if (!$reset->save()) {
                Yii::error([
                    'action' => 'create reset',
                    'status' => 'error',
                    'user_id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'errors' => $reset->getFirstErrors(),
                ]);
                throw new Exception(implode(", ", $reset->getFirstErrors()));
            }

            Yii::info([
                'action' => 'create reset',
                'status' => 'success',
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'reset_uid' => $reset->uid,
            ]);
        }

        // TODO: send the reset email
    }

    /**
     * Calculate and return the expiration datetime for a new reset.
     *
     * @return string
     */
    public static function calculateExpireTime(): string
    {
        return MySqlDateTime::relativeTime(self::LIFETIME);
    }

    /**
     * Check whether this reset has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return strtotime($this->expires) < time();
    }

    /**
     * Generate a new verification code.
     *
     * @return string
     */
    private static function generateCode(): string
    {
        $codeLength = Yii::$app->params['reset']['codeLength'] ?? 6;
        return Utils::getRandomDigits(static::CODE_LENGTH);
    }
}
