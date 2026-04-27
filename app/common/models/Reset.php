<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/**
 * Class Reset
 * @package common\models
 */
class Reset extends ResetBase
{
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
            ],
            parent::rules()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return [
            'uid',
            'type',
            'code',
            'attempts',
            'expires' => function ($model) {
                return Utils::getIso8601($model->expires);
            },
            'created' => function ($model) {
                return Utils::getIso8601($model->created);
            },
        ];
    }

    /**
     * Find an existing reset for the given user or create a new one.
     *
     * @param User $user
     * @return Reset
     * @throws ServerErrorHttpException
     */
    public static function findOrCreate(User $user): Reset
    {
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
                throw new ServerErrorHttpException('Unable to create reset record');
            }

            Yii::warning([
                'action' => 'create reset',
                'status' => 'created',
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'reset_uid' => $reset->uid,
            ]);
        } else {
            Yii::warning([
                'action' => 'find reset',
                'status' => 'found',
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'reset_uid' => $reset->uid,
            ]);
        }

        return $reset;
    }

    /**
     * Calculate and return the expiration datetime for a new reset.
     *
     * @return string
     */
    public static function calculateExpireTime(): string
    {
        $lifetimeMinutes = Yii::$app->params['reset']['lifetimeMinutes'] ?? 60;
        return MySqlDateTime::relativeTime('+' . $lifetimeMinutes . ' minutes');
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
        return Utils::getRandomDigits($codeLength);
    }
}
