<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "reset".
 *
 * @property int $id
 * @property string $uid
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
            [['uid', 'user_id', 'type', 'expires', 'created'], 'required'],
            [['user_id', 'attempts'], 'integer'],
            [['type'], 'string'],
            [['expires', 'disable_until', 'created'], 'safe'],
            [['uid'], 'string', 'max' => 32],
            [['code'], 'string', 'max' => 64],
            [['email'], 'string', 'max' => 255],
            [['uid'], 'unique'],
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
            'id'           => Yii::t('app', 'ID'),
            'uid'          => Yii::t('app', 'Uid'),
            'user_id'      => Yii::t('app', 'User ID'),
            'type'         => Yii::t('app', 'Type'),
            'code'         => Yii::t('app', 'Code'),
            'attempts'     => Yii::t('app', 'Attempts'),
            'expires'      => Yii::t('app', 'Expires'),
            'disable_until' => Yii::t('app', 'Disable Until'),
            'created'      => Yii::t('app', 'Created'),
            'email'        => Yii::t('app', 'Email'),
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
