<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class Reset
 * @package common\models
 */
class Reset extends ResetBase
{
    public const string LIFETIME = '60 minutes';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return ArrayHelper::merge(
            [
                [
                    'uuid', 'default', 'value' => Uuid::uuid4()->toString(),
                ],
                [
                    ['created'], 'default', 'value' => MySqlDateTime::now(),
                ],
                [
                    ['expires'], 'default', 'value' => static::calculateExpireTime(),
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
        // Prevent any properties of this class from being returned in a JSON response.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'uuid' => Yii::t('app', 'UUID'),
        ]);
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
                'reset_uuid' => $reset->uuid,
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
        return MySqlDateTime::relativeTime(static::LIFETIME);
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
}
