<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "reset".
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $type
 * @property string|null $code
 * @property int $attempts
 * @property string $expires
 * @property string|null $disable_until
 * @property string $created
 * @property string|null $email
 *
 * @property User $user
 */
class ResetBase extends \yii\db\ActiveRecord
{
    /**
     * ENUM field values
     */
    public const TYPE_PRIMARY = 'primary';
    public const TYPE_METHOD = 'method';
    public const TYPE_SUPERVISOR = 'supervisor';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reset';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uuid', 'user_id', 'expires', 'created'], 'required'],
            [['user_id'], 'integer'],
            [['expires', 'created'], 'safe'],
            [['uuid'], 'string', 'max' => 64],
            [['uuid'], 'unique'],
            [['user_id'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uuid' => Yii::t('app', 'Uuid'),
            'user_id' => Yii::t('app', 'User ID'),
            'expires' => Yii::t('app', 'Expires'),
            'created' => Yii::t('app', 'Created'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
